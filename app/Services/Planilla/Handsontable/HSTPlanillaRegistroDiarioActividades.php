<?php

namespace App\Services\Planilla\Handsontable;

use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Services\Planilla\PlanillaMensualServicio;
use Illuminate\Support\Carbon;

class HSTPlanillaRegistroDiarioActividades
{
    /** Filas de la tabla del registro diario: un trabajador por fila (tramos campo_N…salida_N) y las filas de cuadrilla. */
    public function obtenerRegistroDiarioPlanilla($fecha)
    {
        $planillaDetalles = app(PlanillaMensualServicio::class)->obtenerPlanillaXFecha($fecha);

        // Todos los registros del día de una vez (antes: dos consultas por trabajador)
        $registros = PlanRegistroDiario::with(['detalles' => fn($q) => $q->orderBy('orden')->orderBy('hora_inicio')->orderBy('id')])
            ->whereIn('plan_det_men_id', $planillaDetalles->pluck('id'))
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('plan_det_men_id');

        $hora = fn($h) => $h ? Carbon::parse($h)->format('H:i') : '';

        // Contrato de planilla agraria de cada trabajador respecto a ese día (la lista es del mes: quien cesó sigue en ella).
        // Se usa para bloquear los días fuera de contrato y para el menú contextual ("Contrato: … vigente").
        $dia = Carbon::parse($fecha)->toDateString();
        $contratos = \App\Models\PlanContrato::whereIn('plan_empleado_id', $planillaDetalles->pluck('plan_empleado_id'))
            ->where('tipo_planilla', 'agraria')
            ->orderBy('fecha_inicio')
            ->get(['id', 'plan_empleado_id', 'fecha_inicio', 'fecha_fin', 'motivo_cese_sunat'])
            ->groupBy('plan_empleado_id');
        $contratoDelDia = function ($empleadoId) use ($contratos, $dia) {
            $lista = $contratos->get($empleadoId) ?? collect();
            $cubre = $lista->first(fn($c) => $c->fecha_inicio->toDateString() <= $dia && (!$c->fecha_fin || $c->fecha_fin->toDateString() >= $dia));
            // Si ninguno cubre el día: el último que terminó antes, o el próximo que empieza después
            $c = $cubre ?? $lista->filter(fn($c) => $c->fecha_inicio->toDateString() <= $dia)->last() ?? $lista->first();
            if (!$c) {
                return ['estado' => 'ninguno', 'texto' => 'Sin contrato de planilla agraria'];
            }
            $periodo = $c->fecha_inicio->format('d/m/Y') . ' – ' . ($c->fecha_fin ? $c->fecha_fin->format('d/m/Y') : 'sin fin');
            $estado = $cubre ? 'vigente' : ($c->fecha_inicio->toDateString() > $dia ? 'por_iniciar' : 'terminado');
            return ['estado' => $estado, 'texto' => $periodo];
        };

        // 1️⃣ Obtener los registros de planilla (empleados)
        $empleados = $planillaDetalles->map(function ($planillaMensualDetalle) use ($registros, $hora, $contratoDelDia) {
            $registro = $registros->get($planillaMensualDetalle->id);
            $contrato = $contratoDelDia($planillaMensualDetalle->plan_empleado_id);

            $empleadoData = [
                'plan_men_detalle_id' => $planillaMensualDetalle->id,
                'plan_empleado_id' => $planillaMensualDetalle->plan_empleado_id,
                'sin_contrato' => $contrato['estado'] !== 'vigente',
                'contrato_estado' => $contrato['estado'],
                'contrato_texto' => $contrato['texto'],
                'documento' => $planillaMensualDetalle->documento,
                'nombres' => mb_strtoupper($planillaMensualDetalle->nombres),
                'asistencia' => $registro->asistencia ?? '',
                'total_horas' => $registro->total_horas ?? 0,
                'total_bono' => $registro->total_bono ?? '',
            ];

            foreach ($registro?->detalles ?? [] as $i => $detalle) {
                $n = $i + 1;
                $empleadoData["campo_{$n}"] = $detalle->campo_nombre ?? '';
                $empleadoData["labor_{$n}"] = $detalle->codigo_labor ?? '';
                $empleadoData["entrada_{$n}"] = $hora($detalle->hora_inicio);
                $empleadoData["salida_{$n}"] = $hora($detalle->hora_fin);
            }

            return $empleadoData;
        })->toArray();

        // 2️⃣ Buscar el resumen de cuadrilla guardado en PlanResumenDiario
        $resumen = PlanResumenDiario::whereDate('fecha', $fecha)->first();

        if ($resumen && $resumen->resumen_cuadrilla) {
            $resumenCuadrilla = json_decode($resumen->resumen_cuadrilla, true) ?: [];

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

                foreach ($fila['labores'] ?? [] as $i => $labor) {
                    $n = $i + 1;
                    $nuevo["campo_{$n}"] = $labor['campo'] ?? '';
                    $nuevo["labor_{$n}"] = $labor['labor'] ?? '';
                    $nuevo["entrada_{$n}"] = isset($labor['hora_inicio']) ? $hora($labor['hora_inicio']) : '';
                    $nuevo["salida_{$n}"] = isset($labor['hora_fin']) ? $hora($labor['hora_fin']) : '';
                }

                $empleados[] = $nuevo;
            }
        }

        // 4️⃣ Devolver el conjunto completo (empleados + resumen cuadrilla)
        return $empleados;
    }
}
