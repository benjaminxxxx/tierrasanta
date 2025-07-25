<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\Actividad;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use App\Models\ReporteDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaServicio;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class EmpleadoServicio
{
    public static function obtenerReporteMensual($anio, $mes)
    {
        $diasMes = [];
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1);
        $diasEnMes = $fechaInicio->daysInMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, $diasEnMes);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        $empleados = ReporteDiario::with('detalles')->whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
        $empleadosGeneral = $empleados->keyBy('documento')->toArray();
        foreach ($periodo as $fecha) {
            $diasMes[] = $fecha;
        }
        foreach ($empleados as $empleado) {
            $documento = $empleado->documento;
            $fechaStr = $empleado->fecha;
            // Asegurar que el empleado esté registrado
            if (!isset($empleadosGeneral[$documento])) {
                $empleadosGeneral[$documento] = [
                    'documento' => $documento,
                    'nombre' => $empleado->nombre ?? '', // ajusta si necesitas más campos
                    'detalles' => [],
                ];
            }

            // Asignar directamente los detalles del día
            foreach ($empleado->detalles as $detalle) {
                $empleadosGeneral[$documento]['detalles'][$fechaStr][] = [
                    'labor' => $detalle->labor
                ];
            }
        }
        /*
        foreach ($empleadosGeneral as $indice => $empleado) {
            $documento = $empleado['documento'];
            $empleadosGeneral[$indice]['detalles'] = [];
            foreach ($periodo as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');
                $asistencia = $empleados->first(function ($item) use ($fechaStr, $documento) {
                    return $item->fecha === $fechaStr && $item->documento === $documento;
                });

                $empleadosGeneral[$indice]['detalles'][$fechaStr] = [];
                if ($asistencia && $asistencia->detalles->count() > 0) {
                    foreach ($asistencia->detalles as $detalle) {
                        $empleadosGeneral[$indice]['detalles'][$fechaStr][] = [
                            'labor' => $detalle->labor
                        ];
                    }
                }

            }
        }*/
        return [
            'empleados' => $empleadosGeneral,
            'diasMes' => $diasMes
        ];
    }
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
