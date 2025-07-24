<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\Actividad;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaServicio;

class EmpleadoServicio
{
    public static function cargarSearchableEmpleados($fecha, $tipoEmpleado = null)
    {
        //empleado o cuadrilla o ambos
        $documentosAgregados = array_keys(ConsolidadoRiego::where('fecha', $fecha)->pluck('regador_documento', 'regador_documento')->toArray());
        $trabajadores = [];
        switch ($tipoEmpleado) {
            case "empleados":
                $trabajadores = Empleado::whereNotIn('documento', $documentosAgregados)
                    ->orderBy('apellido_paterno')
                    ->orderBy('apellido_materno')
                    ->orderBy('nombres')
                    ->get()
                    ->map(function ($empleado) {
                        return [
                            'name' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                            'id' => $empleado->documento,
                        ];
                    })->toArray();
                break;
            default:
                $trabajadores = Cuadrillero::whereNotIn('dni', $documentosAgregados)
                    ->whereNotNull('dni')
                    ->orderBy('nombres')
                    ->get(['dni as documento', 'nombres'])
                    ->map(function ($cuadrillero) {
                        return [
                            'name' => $cuadrillero->nombres,
                            'id' => $cuadrillero->documento,
                        ];
                    })->toArray();
                break;
        }
        return $trabajadores;
    }
    public static function guardarBonificaciones($fecha, $datos)
    {
        // 2️⃣ Para cada fila de datos
        foreach ($datos as $fila) {

            $tipo = $fila['tipo'] ?? null;

            if ($tipo == 'CUADRILLA') {
                CuadrilleroServicio::guardarBonoCuadrilla($fila, $fecha);
            }
            if ($tipo == 'PLANILLA') {
                PlanillaServicio::guardarBonoPlanilla($fila, $fecha);
            }
        }
        PlanillaServicio::calcularBonosTotalesPlanilla($fecha);
    }
}
