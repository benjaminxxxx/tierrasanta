<?php

namespace App\Services\Campo\Actividad;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Qué códigos de labor se hicieron en cada campo y día, con cuántos trabajadores (planilla y cuadrilla) y horas.
 * Lee el registro diario de horas de planilla y cuadrilla (la misma fuente que la BDD de costos, pero al día).
 */
class CampoActividadConsulta
{
    /**
     * Tramos registrados entre dos fechas. Una fila por trabajador, campo, día y código.
     *
     * @return Collection<object{fecha, campo, labor, origen, trabajador_id, minutos}>
     */
    public function tramos(string $desde, string $hasta, ?array $campos = null, array $excluirPlan = [], array $excluirCuad = []): Collection
    {
        $planilla = DB::table('plan_detalles_horas as h')
            ->join('plan_registros_diarios as r', 'r.id', '=', 'h.plan_reg_dia_id')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->whereBetween('r.fecha', [$desde, $hasta])
            ->when($campos !== null, fn($q) => $q->whereIn('h.campo_nombre', $campos ?: ['']))
            ->when($excluirPlan, fn($q) => $q->whereNotIn('d.plan_empleado_id', $excluirPlan))
            ->groupBy('r.fecha', 'h.campo_nombre', 'h.codigo_labor', 'd.plan_empleado_id')
            ->selectRaw("DATE(r.fecha) as fecha, h.campo_nombre as campo, h.codigo_labor as labor, 'planilla' as origen,
                d.plan_empleado_id as trabajador_id, SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)) as minutos");

        $cuadrilla = DB::table('cuad_detalles_horas as h')
            ->join('cuad_registros_diarios as r', 'r.id', '=', 'h.registro_diario_id')
            ->whereBetween('r.fecha', [$desde, $hasta])
            ->when($campos !== null, fn($q) => $q->whereIn('h.campo_nombre', $campos ?: ['']))
            ->when($excluirCuad, fn($q) => $q->whereNotIn('r.cuadrillero_id', $excluirCuad))
            ->groupBy('r.fecha', 'h.campo_nombre', 'h.codigo_labor', 'r.cuadrillero_id')
            ->selectRaw("DATE(r.fecha) as fecha, h.campo_nombre as campo, h.codigo_labor as labor, 'cuadrilla' as origen,
                r.cuadrillero_id as trabajador_id, SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)) as minutos");

        return $planilla->unionAll($cuadrilla)->get()->map(fn($f) => (object) [
            'fecha' => (string) $f->fecha,
            'campo' => (string) $f->campo,
            'labor' => (int) $f->labor,
            'origen' => $f->origen,
            'trabajador_id' => (int) $f->trabajador_id,
            'minutos' => (int) $f->minutos,
        ]);
    }

    /**
     * Histórico: por día y campo, cada código con su etapa, trabajadores de planilla, de cuadrilla y horas.
     *
     * @return array [['fecha', 'campo', 'actividades' => [['codigo', 'labor', 'etapa', 'planilla', 'cuadrilla', 'horas']]]]
     */
    public function historial(string $desde, string $hasta, ?string $campo = null): array
    {
        $tramos = $this->tramos($desde, $hasta, $campo ? [$campo] : null);
        $labores = DB::table('labores')->whereNotNull('codigo')->get(['codigo', 'nombre_labor', 'codigo_mano_obra'])->keyBy('codigo');

        return $tramos->groupBy(fn($t) => "{$t->fecha}|{$t->campo}")
            ->map(function ($filas, $clave) use ($labores) {
                [$fecha, $campo] = explode('|', $clave, 2);
                $actividades = $filas->groupBy('labor')->map(fn($porLabor, $codigo) => [
                    'codigo' => (int) $codigo,
                    'labor' => $labores[$codigo]->nombre_labor ?? '',
                    'etapa' => \App\Services\Campania\Sector\CampaniaSectorReglas::etapaDeManoObra($labores[$codigo]->codigo_mano_obra ?? null),
                    'planilla' => $porLabor->where('origen', 'planilla')->count(),
                    'cuadrilla' => $porLabor->where('origen', 'cuadrilla')->count(),
                    'horas' => round($porLabor->sum('minutos') / 60, 2),
                ])->sortByDesc(fn($a) => $a['planilla'] + $a['cuadrilla'])->values()->all();
                return [
                    'fecha' => $fecha,
                    'campo' => $campo,
                    'actividades' => $actividades,
                ];
            })
            ->sortBy(fn($d) => $d['fecha'] . '|' . str_pad($d['campo'], 12, ' ', STR_PAD_LEFT))
            ->values()->all();
    }
}
