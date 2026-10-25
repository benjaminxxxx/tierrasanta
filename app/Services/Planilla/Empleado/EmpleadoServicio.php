<?php

namespace App\Services\Planilla\Empleado;

use App\Models\Actividad;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\ReporteDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Planilla\PlanillaActividadServicio;
use App\Support\ExcelHelper;
use Carbon\CarbonPeriod;
use Date;
use DB;
use Exception;
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
    public static function cargarSearchableEmpleadosPlanilla()
    {
        return PlanEmpleado::alfabetico()
            ->get()
            ->map(function ($empleado) {
                return [
                    'name' => $empleado->nombre_completo,
                    'id' => $empleado->id,
                ];
            })->toArray();
    }
    public static function cargarSearchableEmpleados($fecha, $tipoEmpleado = null)
    {
        // IDs ya agregados por tipo




        switch ($tipoEmpleado) {
            case "empleados":
                $empleadosUsados = ConsolidadoRiego::where('fecha', $fecha)
                    ->where('trabajador_type', PlanEmpleado::class)
                    ->pluck('trabajador_id')
                    ->toArray();
                // Excluir SOLO empleados usados
                return PlanEmpleado::whereNotIn('id', $empleadosUsados)
                    ->alfabetico()
                    ->get()
                    ->map(function ($empleado) {
                        return [
                            'name' => $empleado->nombre_completo,
                            'id' => $empleado->id,
                        ];
                    })->toArray();

            default:
                // Excluir SOLO cuadrilleros usados
                $cuadrillerosUsados = ConsolidadoRiego::where('fecha', $fecha)
                    ->where('trabajador_type', Cuadrillero::class)
                    ->pluck('trabajador_id')
                    ->toArray();
                return Cuadrillero::whereNotIn('id', $cuadrillerosUsados)
                    ->orderBy('nombres')
                    ->get(['id', 'nombres'])
                    ->map(function ($cuadrillero) {
                        return [
                            'name' => $cuadrillero->nombres,
                            'id' => $cuadrillero->id,
                        ];
                    })->toArray();
        }
    }
    public static function guardarBonificaciones($actividadId, $datos, $numeroRecojos, $mapaMetodos)
    {
        foreach ($datos as $fila) {

            $tipo = $fila['tipo'] ?? null;

            if ($tipo == 'CUADRILLA') {
                CuadrilleroServicio::guardarBonoCuadrilla($fila, $numeroRecojos, $actividadId, $mapaMetodos);
            }
            if ($tipo == 'PLANILLA') {
                PlanillaActividadServicio::guardarBonoPlanilla($fila, $numeroRecojos, $actividadId,$mapaMetodos);
            }
        }
    }
}
