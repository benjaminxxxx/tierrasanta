<?php

namespace App\Services\Campania\Tarjeta;

use App\Models\CampoCampania;
use App\Models\Siembra;
use App\Services\Campania\Cosecha\CampaniaCosechaConsulta;
use App\Services\Campania\Etapa\CampaniaEtapaConsulta;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualReglas;
use App\Services\Campania\Sector\CampaniaSectorConsulta;
use App\Services\Campania\Sector\CampaniaSectorReglas;
use Illuminate\Support\Carbon;

/**
 * Tarjeta de una campaña (vista rápida desde cualquier lista): datos generales, avance, cosecha, y sus siembras.
 *
 * Siembras: los grupos del mapa de etapas son las siembras de la campaña, cada una con su fecha de siembra (un campo
 * sembrado a 4 m y meses después rellenado en el intermedio tiene 2 siembras: dos edades, dos infestaciones, dos
 * cosechas). Se muestra también el registro de siembras del campo como referencia (no tiene los rellenos).
 */
class CampaniaTarjetaConsulta
{
    public function __construct(
        private CampaniaSectorConsulta $sectores,
        private CampaniaCosechaConsulta $cosecha,
        private CampaniaEtapaConsulta $etapas,
    ) {
    }

    public function tarjeta(CampoCampania $c): array
    {
        $hoy = now()->toDateString();
        $inicio = Carbon::parse($c->fecha_inicio)->toDateString();
        $fin = CampaniaSectorConsulta::fin($c);
        $corte = min($fin, $hoy);
        $infestacion = $c->infestacion_fecha ? Carbon::parse($c->infestacion_fecha)->toDateString() : null;

        $avance = $inicio <= $corte
            ? CampaniaInformeMensualReglas::avanceVegetativo($inicio, $infestacion && $infestacion <= $corte ? $infestacion : null, $corte)
            : null;
        $cosecha = $this->cosecha->estado($c);
        $etapa = $this->etapas->etapasActuales(collect([$c]))[$c->id] ?? null;

        // Grupos del mapa de etapas = siembras de la campaña, con su etapa al día (o al cierre)
        $grupos = $this->sectores->sectores($c);
        $hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
        $dibujo = $grupos ? $this->sectores->dibujo($c, $hileras, $grupos, $corte) : null;

        // Registro de siembras del campo: la que dio inicio a la campaña y las de durante la campaña
        $registro = Siembra::where('campo_nombre', $c->campo)->orderBy('fecha_siembra')->get(['id', 'fecha_siembra']);
        $base = $registro->filter(fn($s) => $s->fecha_siembra->toDateString() <= $inicio)->last();
        $durante = $registro->filter(fn($s) => $s->fecha_siembra->toDateString() > $inicio && $s->fecha_siembra->toDateString() <= $fin)->values();
        $siembras = collect($base ? [$base] : [])->concat($durante)->values()
            ->map(fn($s, $k) => [
                'fecha' => $s->fecha_siembra->toDateString(),
                'meses' => round($s->fecha_siembra->floatDiffInMonths(Carbon::parse($corte)), 1),
                'durante' => $s->fecha_siembra->toDateString() > $inicio,
            ])->all();

        $listaGrupos = [];
        foreach ($grupos as $k => $g) {
            $actual = CampaniaSectorConsulta::etapaEn($g['etapas'], $corte);
            $desde = collect($g['etapas'])->where('etapa', $actual)->where('fecha_desde', '<=', $corte)->max('fecha_desde');
            $listaGrupos[] = [
                'nombre' => $g['nombre'],
                'hileras' => "{$g['hilera_desde']}–{$g['hilera_hasta']}",
                'n_hileras' => (int) $g['hilera_hasta'] - (int) $g['hilera_desde'] + 1,
                'etapa' => $actual,
                'etapa_nombre' => CampaniaSectorReglas::nombre($actual),
                'color' => CampaniaSectorReglas::color($actual),
                'desde' => $desde,
                'dias_en_etapa' => $desde ? (int) Carbon::parse($desde)->diffInDays(Carbon::parse($corte)) : null,
                'tramos' => $dibujo['lineas'][$k]['tramos'] ?? [],
                'siembra' => $g['fecha_siembra'] ? ['fecha' => $g['fecha_siembra'],
                    'meses' => round(Carbon::parse($g['fecha_siembra'])->floatDiffInMonths(Carbon::parse($corte)), 1)] : null,
                'cosechada' => collect($g['etapas'])->contains('etapa', 'cosecha'),
            ];
        }

        $aviso = null;
        if (count($grupos) > 1 && collect($grupos)->contains(fn($g) => !$g['fecha_siembra'])) {
            $aviso = 'Hay siembras sin fecha de siembra: complétalas en el mapa de etapas (cada siembra existe porque se sembró en otra fecha).';
        } elseif (count($grupos) > 1 && !$c->fecha_fin && collect($listaGrupos)->contains(fn($g) => !$g['cosechada'])) {
            $aviso = 'Campaña con ' . count($grupos) . ' siembras: se cierra con la cosecha de la última (' . collect($listaGrupos)->reject(fn($g) => $g['cosechada'])->pluck('nombre')->implode(', ') . ' aún sin cosechar).';
        }

        return [
            'id' => $c->id,
            'campo' => $c->campo,
            'nombre' => $c->nombre_campania,
            'area' => $c->area ? (float) $c->area : null,
            'variedad' => $c->variedad_tuna,
            'sistema' => $c->sistema_cultivo,
            'inicio' => $inicio,
            'fin' => $c->fecha_fin ? $fin : null,
            'cerrada' => (bool) $c->fecha_fin,
            'dias' => (int) Carbon::parse($inicio)->diffInDays(Carbon::parse($corte)) + 1,
            'avance' => $avance,
            'etapa' => $etapa,
            'cosecha' => $cosecha,
            'hileras' => $hileras,
            'filas' => $dibujo['filas'] ?? [],
            'grupos' => $listaGrupos,
            'siembras' => $siembras,
            'aviso_siembras' => $aviso,
            'linea' => $dibujo ? ['inicio' => $dibujo['inicio'], 'fin' => $dibujo['fin'], 'marca' => $dibujo['marca']] : null,
        ];
    }
}
