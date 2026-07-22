<?php

namespace App\Services;

use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Models\PlanEmpleado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanEmpleadoServicio
{
    /**
     * Obtiene los empleados contratados/activos en el periodo que NO tienen fecha de nacimiento.
     */
    public function obtenerEmpleadosSinFechaNacimiento(int $mes, int $anio): Collection
    {
        $planillaServicio = app(PlanillaEmpleadoServicio::class);
        $empleados = $planillaServicio->obtenerPlanillaAgraria($mes, $anio);

        return $empleados->filter(function ($empleado) {
            return empty($empleado->fecha_nacimiento);
        });
    }

    /**
     * Actualiza masivamente las fechas de nacimiento.
     */
    public function actualizarFechasNacimientoMasivo(array $fechasNacimiento): int
    {
        $actualizados = 0;

        DB::transaction(function () use ($fechasNacimiento, &$actualizados) {
            foreach ($fechasNacimiento as $empleadoId => $fecha) {
                if (!empty(trim($fecha))) {
                    PlanEmpleado::where('id', $empleadoId)->update([
                        'fecha_nacimiento' => $fecha,
                    ]);
                    $actualizados++;
                }
            }
        });

        return $actualizados;
    }
}