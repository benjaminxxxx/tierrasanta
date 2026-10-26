<?php

namespace App\Services\Cuadrilla\Resumen;

use Illuminate\Support\Facades\DB;

/**
 * Resumen anual de cuadrilla (Cuadrilla → Resumen anual), en vivo desde el registro diario.
 *
 * - Jornal del día: horas no a destajo / 8 × (costo personalizado o jornal aplicado), igual que
 *   CuadRegistroDiario::costo_dia y CuadrillaGrupoConsulta.
 * - Bonos: cuad_bonos_actividades del día.
 * - El mes es el de la fecha trabajada (no el del pago).
 */
class CuadrillaResumenAnualConsulta
{
    /**
     * @return array{meses: array<int, array{jornal: float, bono: float, total: float, dias: int, cuadrilleros: int}>,
     *               totales: array{jornal: float, bono: float, total: float}}
     */
    public function delAnio(int $anio): array
    {
        $filas = DB::table('cuad_registros_diarios as r')
            ->leftJoinSub(
                DB::table('cuad_bonos_actividades')->selectRaw('registro_diario_id, SUM(total_bono) AS bonos')->groupBy('registro_diario_id'),
                'b', 'b.registro_diario_id', '=', 'r.id'
            )
            ->whereYear('r.fecha', $anio)
            ->selectRaw('MONTH(r.fecha) AS mes')
            ->selectRaw('SUM(GREATEST(0, COALESCE(r.total_horas, 0) - COALESCE(r.horas_destajo, 0)) / 8 * COALESCE(NULLIF(r.costo_personalizado_dia, 0), r.jornal_aplicado, 0)) AS jornal')
            ->selectRaw('SUM(COALESCE(b.bonos, 0)) AS bono')
            ->selectRaw('COUNT(DISTINCT CASE WHEN r.total_horas > 0 THEN r.fecha END) AS dias')
            ->selectRaw('COUNT(DISTINCT CASE WHEN r.total_horas > 0 THEN r.cuadrillero_id END) AS cuadrilleros')
            ->groupByRaw('MONTH(r.fecha)')
            ->get()
            ->keyBy('mes');

        $meses = [];
        $totales = ['jornal' => 0.0, 'bono' => 0.0, 'total' => 0.0];
        for ($m = 1; $m <= 12; $m++) {
            $f = $filas->get($m);
            $jornal = round((float) ($f->jornal ?? 0), 2);
            $bono = round((float) ($f->bono ?? 0), 2);
            $meses[$m] = [
                'jornal' => $jornal,
                'bono' => $bono,
                'total' => round($jornal + $bono, 2),
                'dias' => (int) ($f->dias ?? 0),
                'cuadrilleros' => (int) ($f->cuadrilleros ?? 0),
            ];
            $totales['jornal'] += $jornal;
            $totales['bono'] += $bono;
            $totales['total'] += $jornal + $bono;
        }

        return ['meses' => $meses, 'totales' => $totales];
    }
}
