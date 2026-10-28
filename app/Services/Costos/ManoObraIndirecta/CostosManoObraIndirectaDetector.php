<?php

namespace App\Services\Costos\ManoObraIndirecta;

use App\Models\CostoMensual;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente: meses ya terminados sin mano de obra indirecta calculada, o calculada "no concluyente".
 * Una subtarea por mes; desaparece al calcularlo (concluyente).
 */
class CostosManoObraIndirectaDetector
{
    public const TIPO = 'costos-mano-obra-indirecta';

    public function __construct(private TareaPendienteServicio $registrador)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $guardados = CostoMensual::get(['anio', 'mes', 'moi_calculado_en', 'moi_concluyente'])->keyBy(fn($c) => sprintf('%04d-%02d', $c->anio, $c->mes));
        $hijas = [];
        $ultimo = Carbon::today()->startOfMonth()->subMonth();
        // Desde la vigencia del sistema: los meses de la empresa anterior no son tarea de esta
        for ($m = CostosManoObraIndirectaReglas::primerMes(); $m->lte($ultimo); $m->addMonth()) {
            $clave = $m->format('Y-m');
            $c = $guardados->get($clave);
            if ($c?->moi_calculado_en && $c->moi_concluyente) {
                continue;
            }
            $periodo = ucfirst($m->translatedFormat('F Y'));
            $hijas[] = [
                'clave' => $clave,
                'anio' => $m->year,
                'mes' => $m->month,
                'titulo' => $c?->moi_calculado_en ? "{$periodo}: mano de obra indirecta no concluyente" : "{$periodo}: mano de obra indirecta sin calcular",
                'descripcion' => $c?->moi_calculado_en
                    ? 'Se calculó el ' . $c->moi_calculado_en->format('d/m/Y') . ' con avisos pendientes (planilla, kardex o distribuciones). Resuélvelos y recalcula.'
                    : 'El mes ya terminó y su mano de obra indirecta (todo lo gastado en FDM) no se ha calculado.',
            ];
        }

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Mano de obra indirecta por calcular',
            'descripcion' => count($hijas) . ' mes(es) terminados sin calcular o con cálculo no concluyente.',
            'variante' => 'warning',
            'cantidad_afectados' => count($hijas),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Ir a mano de obra indirecta', 'tipo_accion' => 'link', 'url' => route('costos.mano_obra_indirecta')]],
        ]);

        foreach ($hijas as $h) {
            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $h['clave'],
                'parent_id' => $padre?->id,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'titulo' => $h['titulo'],
                'descripcion' => $h['descripcion'],
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => 'Calcular', 'tipo_accion' => 'link', 'url' => route('costos.mano_obra_indirecta', ['anio' => $h['anio'], 'mes' => $h['mes']])]],
            ]);
        }

        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')->whereNotNull('parent_id')
            ->whereNotIn('clave', array_column($hijas, 'clave'))->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $vieja->clave, 'cantidad_afectados' => 0]));
    }
}
