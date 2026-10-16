<?php

namespace App\Services\Campania\InformeMensual;

use App\Models\CampaniaInformeMensual;
use App\Models\CampoCampania;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Estado de los informes mensuales: qué campañas corresponden a un mes, cuáles ya se generaron y qué falta
 * (informe del mes anterior, campañas sin fecha de fin reemplazadas por otra del mismo campo).
 */
class CampaniaInformeMensualEstadoConsulta
{
    /**
     * Campañas activas del mes, ordenadas por campo e inicio. Una campaña sin fecha de fin cuyo campo ya tiene otra
     * campaña posterior iniciada hasta ese mes se considera reemplazada (le falta registrar la fecha de fin).
     *
     * @return array{activas: Collection<int, CampoCampania>, reemplazadas: array<int, array{campania: CampoCampania, motivo: string}>}
     */
    public function campaniasDelMes(int $anio, int $mes): array
    {
        $inicioMes = CampaniaInformeMensualReglas::inicioMes($anio, $mes)->toDateString();
        $finMes = CampaniaInformeMensualReglas::finMes($anio, $mes)->toDateString();

        $candidatas = CampoCampania::where('fecha_inicio', '<=', $finMes)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes))
            ->get();
        $siguientes = CampoCampania::where('fecha_inicio', '<=', $finMes)->get(['id', 'campo', 'nombre_campania', 'fecha_inicio'])->groupBy('campo');

        $activas = collect();
        $reemplazadas = [];
        foreach ($candidatas as $c) {
            $posterior = !$c->fecha_fin
                ? ($siguientes[$c->campo] ?? collect())->filter(fn($o) => $o->id !== $c->id && $o->fecha_inicio > $c->fecha_inicio)->sortBy('fecha_inicio')->first()
                : null;
            if ($posterior) {
                $reemplazadas[] = [
                    'campania' => $c,
                    'motivo' => "No tiene fecha de fin y el campo {$c->campo} ya tiene la campaña {$posterior->nombre_campania} desde "
                        . Carbon::parse($posterior->fecha_inicio)->format('d/m/Y') . '. Registra su fecha de fin.',
                ];
                continue;
            }
            $activas->push($c);
        }

        return [
            'activas' => $activas->sort(fn($a, $b) => [$this->claveCampo($a->campo), $a->fecha_inicio] <=> [$this->claveCampo($b->campo), $b->fecha_inicio])->values(),
            'reemplazadas' => $reemplazadas,
        ];
    }

    /**
     * Filas del mes: cada campaña activa con su informe (si existe) y lo que le falta.
     *
     * @return array{filas: array<int, array>, reemplazadas: array, resumen: array{total:int, generados:int, sin_anterior:int}}
     */
    public function estadoMes(int $anio, int $mes): array
    {
        $campanias = $this->campaniasDelMes($anio, $mes);
        $ids = $campanias['activas']->pluck('id');
        $informes = CampaniaInformeMensual::with('generadoPor:id,name')->where('anio', $anio)->where('mes', $mes)
            ->whereIn('campo_campania_id', $ids)->get()->keyBy('campo_campania_id');
        $anterior = CampaniaInformeMensualReglas::inicioMes($anio, $mes)->subMonthNoOverflow();
        $anteriores = CampaniaInformeMensual::where('anio', $anterior->year)->where('mes', $anterior->month)
            ->whereIn('campo_campania_id', $ids)->pluck('campo_campania_id')->flip();
        $inicioMes = CampaniaInformeMensualReglas::inicioMes($anio, $mes);

        $filas = [];
        foreach ($campanias['activas'] as $c) {
            $empiezaEsteMes = Carbon::parse($c->fecha_inicio)->gte($inicioMes);
            $filas[] = [
                'campania' => $c,
                'informe' => $informes->get($c->id),
                'es_cierre' => CampaniaInformeMensualReglas::esCierre($c, $anio, $mes),
                'es_nueva' => $empiezaEsteMes,
                'falta_anterior' => !$empiezaEsteMes && !isset($anteriores[$c->id]),
                'fecha_corte' => CampaniaInformeMensualReglas::fechaCorte($c, $anio, $mes)->toDateString(),
            ];
        }

        return [
            'filas' => $filas,
            'reemplazadas' => $campanias['reemplazadas'],
            'resumen' => [
                'total' => count($filas),
                'generados' => count(array_filter($filas, fn($f) => $f['informe'])),
                'sin_anterior' => count(array_filter($filas, fn($f) => $f['falta_anterior'])),
            ],
        ];
    }

    /**
     * Meses de una campaña con su informe y si falta el del mes anterior.
     *
     * @return array<int, array{anio:int, mes:int, periodo:string, informe:?CampaniaInformeMensual, es_cierre:bool, falta_anterior:bool, fecha_corte:string}>
     */
    public function estadoCampania(CampoCampania $campania): array
    {
        $informes = CampaniaInformeMensual::with('generadoPor:id,name')->where('campo_campania_id', $campania->id)->get()->keyBy('periodo');
        $meses = CampaniaInformeMensualReglas::mesesDeCampania($campania);
        $filas = [];
        foreach ($meses as $i => $m) {
            $filas[] = $m + [
                'informe' => $informes->get($m['periodo']),
                'es_cierre' => CampaniaInformeMensualReglas::esCierre($campania, $m['anio'], $m['mes']),
                'falta_anterior' => $i > 0 && !$informes->has($meses[$i - 1]['periodo']),
                'fecha_corte' => CampaniaInformeMensualReglas::fechaCorte($campania, $m['anio'], $m['mes'])->toDateString(),
            ];
        }
        return array_reverse($filas);
    }

    /** Orden natural de campos: 1, 2, … 10, A1, A2 … A10, B1 … */
    private function claveCampo(string $campo): string
    {
        return preg_replace_callback('/\d+/', fn($m) => str_pad($m[0], 4, '0', STR_PAD_LEFT), mb_strtoupper($campo));
    }
}
