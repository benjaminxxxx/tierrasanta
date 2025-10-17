<?php

namespace App\Services;
use App\Models\PlanSueldo;
use DB;

class PlanSueldoServicio
{
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