<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\Actividad;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaServicio;

class EmpleadoServicio
{
    public static function guardarBonificaciones($fecha, $datos)
    {
        // 2️⃣ Para cada fila de datos
        foreach ($datos as $fila) {

            $tipo = $fila['tipo'] ?? null;

            if($tipo == 'CUADRILLA'){
                CuadrilleroServicio::guardarBonoCuadrilla($fila,$fecha);
            }
            if($tipo == 'PLANILLA'){
                PlanillaServicio::guardarBonoPlanilla($fila,$fecha);
            }
        }
        PlanillaServicio::calcularBonosTotalesPlanilla($fecha);
    }
}
