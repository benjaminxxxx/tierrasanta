<?php

namespace App\Services;
use App\Models\PlanMensualDetalle;
use App\Models\PlanSueldo;
use DB;
use Exception;
use Illuminate\Support\Carbon;

class PlanSueldoServicio
{
    /**
     * Obtiene el último sueldo vigente para una lista de empleados.
     */
    public function obtenerSueldosPorMes($mes, $anio)
    {
        // Fecha límite para validar sueldos activos
        $fechaLimite = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        // 1. Traer todos los empleados del mes
        $empleados = PlanMensualDetalle::whereHas('planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)->where('anio', $anio);
        })
            ->get(['plan_empleado_id', 'nombres', 'documento']);

        if ($empleados->isEmpty()) {
            throw new Exception("No existen empleados registrados en la planilla mensual de {$mes}/{$anio}.");
        }

        $empleadoIds = $empleados->pluck('plan_empleado_id')->toArray();

        // 2. Sueldos por empleado
        $sueldos = PlanSueldo::whereIn('plan_empleado_id', $empleadoIds)
            ->where('fecha_inicio', '<=', $fechaLimite)
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->groupBy('plan_empleado_id')
            ->map(function ($historial) {
                return (float) $historial->first()->sueldo;
            });

        // 3. Validación: que todos tengan sueldo
        foreach ($empleados as $emp) {
            if (!$sueldos->has($emp->plan_empleado_id)) {

                throw new Exception(
                    "ERROR CRÍTICO: El empleado {$emp->nombres} ({$emp->documento}) " .
                    "no tiene un sueldo registrado vigente al {$fechaLimite->format('d/m/Y')}. " .
                    "Debe registrar un sueldo en la tabla plan_sueldos."
                );
            }
        }

        return $sueldos->toArray();
    }

    public function listar()
    {
        return PlanSueldo::all();
    }

    public function obtenerPorId($id)
    {
        return PlanSueldo::find($id);
    }

    public function crear(array $datos)
    {
        return PlanSueldo::create($datos);
    }

    public function actualizar($id, array $datos)
    {
        $planSueldo = PlanSueldo::find($id);
        if ($planSueldo) {
            $planSueldo->update($datos);
        }
        return $planSueldo;
    }

    public function eliminar($id)
    {
        $planSueldo = PlanSueldo::find($id);

        if (!$planSueldo) {
            return false;
        }

        DB::beginTransaction();
        try {
            $empleadoId = $planSueldo->plan_empleado_id;

            // Eliminar el sueldo actual
            $planSueldo->delete();

            // Buscar el último sueldo anterior
            $ultimoSueldoAnterior = PlanSueldo::where('plan_empleado_id', $empleadoId)
                ->orderByDesc('fecha_inicio')
                ->first();

            if ($ultimoSueldoAnterior) {
                $ultimoSueldoAnterior->update(['fecha_fin' => null]);
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

}