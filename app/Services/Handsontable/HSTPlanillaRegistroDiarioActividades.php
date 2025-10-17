<?php

namespace App\Services\Handsontable;

use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Services\PlanillaMensualServicio;
use Illuminate\Support\Carbon;
class HSTPlanillaRegistroDiarioActividades
{
    public function obtenerRegistroDiarioPlanilla($fecha)
    {
        $planillaDetalles = app(PlanillaMensualServicio::class)->obtenerPlanillaXFecha($fecha);

        // 1️⃣ Obtener los registros de planilla (empleados)
        $empleados = $planillaDetalles->map(function ($planillaMensualDetalle) use ($fecha) {
            $registro = PlanRegistroDiario::with('detalles')
                ->where('plan_det_men_id', $planillaMensualDetalle->id)
                ->whereDate('fecha', $fecha)
                ->first();

            $empleadoData = [
                'plan_men_detalle_id' => $planillaMensualDetalle->id,
                'documento' => $planillaMensualDetalle->documento,
                'nombres' => mb_strtoupper($planillaMensualDetalle->nombres),
                'asistencia' => $registro->asistencia ?? '',
                'total_horas' => $registro->total_horas ?? 0,
                'total_bono' => $registro->total_bono ?? '',
            ];

            if ($registro && $registro->detalles->count() > 0) {
                foreach ($registro->detalles()->orderBy('orden')->get() as $i => $detalle) {
                    $empleadoData['campo_' . ($i + 1)] = $detalle->campo_nombre ?? '';
                    $empleadoData['labor_' . ($i + 1)] = $detalle->codigo_labor ?? '';
                    $empleadoData['entrada_' . ($i + 1)] = $detalle->hora_inicio
                        ? Carbon::parse($detalle->hora_inicio)->format('G.i')
                        : '';
                    $empleadoData['salida_' . ($i + 1)] = $detalle->hora_fin
                        ? Carbon::parse($detalle->hora_fin)->format('G.i')
                        : '';
                }
            }

            return $empleadoData;
        })->toArray();

        // 2️⃣ Buscar el resumen de cuadrilla guardado en PlanResumenDiario
        $resumen = PlanResumenDiario::whereDate('fecha', $fecha)->first();

        if ($resumen && $resumen->resumen_cuadrilla) {
            $resumenCuadrilla = json_decode($resumen->resumen_cuadrilla, true);

            // 3️⃣ Convertir cada bloque del resumen en una fila de “empleado ficticio”
            foreach ($resumenCuadrilla as $fila) {
                $nuevo = [
                    'plan_men_detalle_id' => null,
                    'documento' => null,
                    'nombres' => 'CUADRILLA',
                    'asistencia' => '',
                    'total_horas' => formatear_numero($fila['total_horas']) ?? 0,
                    'total_bono' => '',
                    'numero_cuadrilleros' => $fila['numero_cuadrilleros'] ?? 0,
                ];

                // Añadir cada labor como columnas dinámicas (campo/labor/entrada/salida)
                foreach ($fila['labores'] as $i => $labor) {
                    $index = $i + 1;
                    $nuevo['campo_' . $index] = $labor['campo'] ?? '';
                    $nuevo['labor_' . $index] = $labor['labor'] ?? '';
                    $nuevo['entrada_' . $index] = isset($labor['hora_inicio'])
                        ? Carbon::parse($labor['hora_inicio'])->format('G.i')
                        : '';
                    $nuevo['salida_' . $index] = isset($labor['hora_fin'])
                        ? Carbon::parse($labor['hora_fin'])->format('G.i')
                        : '';
                }

                $empleados[] = $nuevo;
            }
        }

        // 4️⃣ Devolver el conjunto completo (empleados + resumen cuadrilla)
        return $empleados;
    }

}