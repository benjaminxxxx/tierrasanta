<?php

namespace App\Services;

use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Services\Excel\Planilla\ExcelPlanillaMensual;
use Illuminate\Support\Carbon;

class PlanillaMensualServicio
{
    public function generarExcel($params)
    {
        return app(ExcelPlanillaMensual::class)->generarPlanillaMensual($params);
    }
    public function guardarOrdenMensualEmpleados($mes, $anio, $listaPlanilla)
    {

        // Buscar o crear el plan mensual
        $planMensual = PlanMensual::firstOrCreate(
            ['mes' => $mes, 'anio' => $anio]
        );

        $planMensualId = $planMensual->id;

        // Obtener los IDs de empleados en la nueva lista
        $nuevosIds = collect($listaPlanilla)->pluck('id')->filter()->unique()->toArray();

        // Obtener los detalles actuales del plan
        $detallesActuales = PlanMensualDetalle::where('plan_mensual_id', $planMensualId)->get();

        // Eliminar solo los detalles cuyos empleados ya no están en la nueva lista
        $detallesAEliminar = $detallesActuales->whereNotIn('plan_empleado_id', $nuevosIds);
        //dd($listaPlanilla,$nuevosIds,$detallesActuales,$detallesAEliminar);
        //dd($detallesAEliminar);
        if ($detallesAEliminar->isNotEmpty()) {
            PlanMensualDetalle::whereIn('id', $detallesAEliminar->pluck('id'))->delete();
        }

        // Actualizar o crear los detalles de los empleados actuales
        foreach ($listaPlanilla as $indiceOrden => $empleado) {

            PlanMensualDetalle::updateOrCreate(
                [
                    'plan_empleado_id' => $empleado['id'],
                    'plan_mensual_id' => $planMensualId,
                ],
                [
                    'nombres' => $empleado['nombres'] ?? null,
                    'documento' => $empleado['documento'] ?? null,
                    'grupo' => $empleado['grupo'] ?? null,
                    'orden' => $indiceOrden + 1,
                    'spp_snp' => $empleado['spp_snp'],
                ]
            );
        }
    }

    public function obtenerPlanillaXFecha($fecha)
    {
        $carbon = Carbon::parse($fecha);
        return $this->obtenerPlanillaXMesAnio($carbon->month, $carbon->year);
    }
    public function obtenerPlanillaXMesAnio($mes, $anio)
    {
        return PlanMensualDetalle::whereHas('planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)
                ->where('anio', $anio);
        })
            ->with([
                'registrosDiarios',
                'empleado.contratos' => function ($q) use ($mes, $anio) {
                    $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
                    $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
                    $q->where('fecha_inicio', '<=', $finMes)
                        ->where(function ($q2) use ($inicioMes) {
                            $q2->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->limit(1);
                },
                'empleado.sueldos' => function ($q) use ($mes, $anio) {
                    $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
                    $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
                    $q->where('fecha_inicio', '<=', $finMes)
                        ->where(function ($q2) use ($inicioMes) {
                            $q2->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->limit(1);
                }
            ])
            ->orderBy('orden')
            ->get();
    }
}