<?php

namespace App\Services\Planilla\Asistencia;

use App\Models\PlanContrato;
use App\Models\PlanMensualPersonal;
use App\Models\PlanRegistroDiario;
use Carbon\Carbon;

/**
 * Resumen de asistencia de planilla por rango de fechas (reporte general).
 *
 * El grupo se lee del contrato vigente en la fecha del registro y el costo por hora del PLAME del mes
 * (plan_mensual_personals.pagado_sueldo_por_hora, el mismo que usa el consolidado de costos).
 * Las columnas grupo / costo_hora de plan_mensual_detalles ya no se llenan (solo hasta 01/2026).
 */
class ResumenAsistenciaPlanillaServicio
{
    public function obtenerResumen(
        $fechaInicio = null,
        $fechaFin = null,
        $grupoSeleccionado = null,
        $filtroNombres = null
    ) {

        if (empty($fechaInicio) || empty($fechaFin)) {
            return [];
        }

        // Contratos que se cruzan con el rango (para filtrar y mostrar el grupo)
        $contratoEnRango = fn($q) => $q->where('fecha_inicio', '<=', $fechaFin)
            ->where(fn($q2) => $q2->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio));

        $registros = PlanRegistroDiario::with(['detalleMensual', 'detalles'])
            ->where('fecha', '>=', $fechaInicio)
            ->where('fecha', '<=', $fechaFin)

            ->when($grupoSeleccionado, function ($query) use ($grupoSeleccionado, $contratoEnRango) {
                if ($grupoSeleccionado === 'SG') {
                    $query->whereDoesntHave('detalleMensual.empleado.contratos',
                        fn($q) => $contratoEnRango($q)->whereNotNull('grupo_codigo'));
                } else {
                    $query->whereHas('detalleMensual.empleado.contratos',
                        fn($q) => $contratoEnRango($q)->where('grupo_codigo', $grupoSeleccionado));
                }
            })

            ->when($filtroNombres, function ($query) use ($filtroNombres) {
                $query->whereHas('detalleMensual', function ($q) use ($filtroNombres) {
                    $q->where(function ($sub) use ($filtroNombres) {
                        $sub->where('nombres', 'like', '%' . $filtroNombres . '%')
                            ->orWhere('documento', 'like', '%' . $filtroNombres . '%');
                    });
                });
            })

            ->get();

        $empleadoIds = $registros->pluck('detalleMensual.plan_empleado_id')->filter()->unique()->values();

        $contratos = PlanContrato::whereIn('plan_empleado_id', $empleadoIds)
            ->where($contratoEnRango)
            ->orderByDesc('fecha_inicio')
            ->get(['plan_empleado_id', 'grupo_codigo', 'fecha_inicio', 'fecha_fin'])
            ->groupBy('plan_empleado_id');

        // Costo por hora del PLAME: [plan_empleado_id-mes-anio => costo]
        $costosHora = PlanMensualPersonal::with('planMensual')
            ->whereIn('plan_empleado_id', $empleadoIds)
            ->whereHas('planMensual', fn($q) => $q
                ->whereRaw('anio * 100 + mes BETWEEN ? AND ?', [
                    Carbon::parse($fechaInicio)->format('Ym'),
                    Carbon::parse($fechaFin)->format('Ym'),
                ]))
            ->get()
            ->mapWithKeys(fn($p) => [
                "{$p->plan_empleado_id}-{$p->planMensual->mes}-{$p->planMensual->anio}" => (float) ($p->pagado_sueldo_por_hora ?? 0),
            ]);

        return $registros->map(function ($r) use ($contratos, $costosHora) {
            $fecha = Carbon::parse($r->fecha);
            $empleadoId = $r->detalleMensual->plan_empleado_id ?? 0;
            $dia = $fecha->toDateString();

            $contrato = ($contratos[$empleadoId] ?? collect())->first(fn($c) =>
                Carbon::parse($c->fecha_inicio)->toDateString() <= $dia
                && ($c->fecha_fin === null || Carbon::parse($c->fecha_fin)->toDateString() >= $dia));

            return [
                'fecha' => $r->fecha,
                'codigo_grupo' => $contrato?->grupo_codigo ?? 'SG',
                'nombres' => $r->detalleMensual->nombres ?? '',
                'costo_x_hora' => $costosHora["{$empleadoId}-{$fecha->month}-{$fecha->year}"] ?? 0,
                'asistencia' => $r->asistencia,
                'total_horas' => $r->total_horas,
                'costo_dia' => $r->costo_dia,
                'total_bono' => $r->total_bono,
                'esta_pagado' => $r->esta_pagado,
                'bono_esta_pagado' => $r->bono_esta_pagado,
                'detalle_campos' => $r->detalles->pluck('campo_nombre', 'campo_nombre')->implode(', '),
            ];
        })->toArray();
    }
}
