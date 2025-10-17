<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanDetalleHora;
use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Models\PlanResumenDiarioTipoAsistencia;
use App\Models\PlanTipoAsistencia;
use Illuminate\Support\Carbon;

class PlanillaRegistroDiarioServicio
{
    public function guardarRegistrosDiarios($fecha, $datos, $totalActividades)
    {
        $errores = [];
        $totalesPorAsistencia = [];

        foreach ($datos as $i => $informacion) {

            $planillaMensualDetalleId = $informacion['plan_men_detalle_id'] ?? null;

            if (!$planillaMensualDetalleId) {
                $errores[] = "Fila " . ($i + 1) . ": falta el ID de detalle mensual.";
                continue;
            }

            $tramos = [];
            $totalHoras = 0;

            for ($x = 1; $x <= $totalActividades; $x++) {

                $inicio = $informacion['entrada_' . $x] ?? null;
                $fin = $informacion['salida_' . $x] ?? null;
                $labor = $informacion['labor_' . $x] ?? null;
                $campo = $informacion['campo_' . $x] ?? null;

                if ($inicio) {
                    $inicio = str_replace('.', ':', $inicio);
                }
                if ($fin) {
                    $fin = str_replace('.', ':', $fin);
                }

                if ($inicio || $fin || $campo || $labor) {
                    // Validación mínima
                    if (!$inicio || !$fin || !$labor) {
                        $errores[] = "Fila " . ($i + 1) . ", tramo {$x}: falta hora o labor.";
                        continue;
                    }

                    // Calcular horas trabajadas (simple)
                    $hInicio = Carbon::parse($inicio);
                    $hFin = Carbon::parse($fin);
                    $horas = $hInicio->floatDiffInHours($hFin);

                    $totalHoras += $horas;

                    $tramos[] = [
                        'codigo_labor' => $labor,
                        'campo_nombre' => $campo,
                        'hora_inicio' => $hInicio->format('H:i'),
                        'hora_fin' => $hFin->format('H:i'),
                    ];
                }
            }

            $asistencia = $informacion['asistencia'] ?? '';

            $registro = PlanRegistroDiario::updateOrCreate(
                [
                    'plan_det_men_id' => $planillaMensualDetalleId,
                    'fecha' => $fecha,
                ],
                [
                    'asistencia' => $asistencia,
                    'total_horas' => $informacion['total_horas']
                ]
            );
            if (trim($asistencia) != '') {
                $totalesPorAsistencia[$asistencia] = ($totalesPorAsistencia[$asistencia] ?? 0) + 1;
            }

            // Si no hay tramos, eliminamos los anteriores
            if (empty($tramos)) {
                $registro->detalles()->delete();
                if (trim($asistencia) == '') {
                    $registro->delete();
                }

                continue;
            }


            // Sincronizar tramos (crea nuevos, elimina ausentes)
            $existentes = $registro->detalles()->get();

            $clave = fn($t) => implode('|', [
                $t['codigo_labor'],
                $t['campo_nombre'],
                Carbon::parse($t['hora_inicio'])->format('H:i'),
                Carbon::parse($t['hora_fin'])->format('H:i'),
            ]);

            $existentesMap = $existentes->keyBy(fn($e) => $clave($e->toArray()));

            $nuevosMap = collect($tramos)->keyBy($clave);

            // Eliminar tramos que ya no existen
            foreach ($existentes as $existente) {
                $k = $clave($existente->toArray());
                if (!$nuevosMap->has($k)) {
                    $existente->delete();
                }
            }

            $orden = 0;
            foreach ($nuevosMap as $k => $nuevo) {

                if (!$existentesMap->has($k)) {
                    $orden++;
                    $registro->detalles()->create([
                        'campo_nombre' => $nuevo['campo_nombre'],
                        'codigo_labor' => $nuevo['codigo_labor'],
                        'hora_inicio' => $nuevo['hora_inicio'],
                        'hora_fin' => $nuevo['hora_fin'],
                        'orden' => $orden,
                    ]);
                }
            }
        }

        if (!empty($totalesPorAsistencia)) {
            $this->actualizarResumenAsistencia($fecha, $totalesPorAsistencia);
        }

        // Retornar errores si los hubo
        return empty($errores)
            ? ['status' => 'ok']
            : ['status' => 'warning', 'errores' => $errores];
    }
    private function actualizarResumenAsistencia($fecha, $totales)
    {
        $resumen = PlanResumenDiario::firstOrCreate(['fecha' => $fecha]);
        $total = 0;
        $codigosNuevos = array_keys($totales);

        PlanResumenDiarioTipoAsistencia::where('plan_res_dia_id', $resumen->id)
            ->whereNotIn('codigo', $codigosNuevos)
            ->delete();

        foreach ($totales as $codigo => $cantidad) {
            $total += $cantidad;
            $registro = PlanResumenDiarioTipoAsistencia::where([
                'plan_res_dia_id' => $resumen->id,
                'codigo' => $codigo,
                'fecha' => $fecha,
            ])->first();

            if (!$registro) {
                // Buscar en el catálogo base
                $tipo = PlanTipoAsistencia::where('codigo', $codigo)->first();
                if (!$tipo) {
                    continue; // código no válido
                }

                PlanResumenDiarioTipoAsistencia::create([
                    'plan_res_dia_id' => $resumen->id,
                    'codigo' => $tipo->codigo,
                    'color' => $tipo->color,
                    'descripcion' => $tipo->descripcion,
                    'horas_jornal' => $tipo->horas_jornal,
                    'tipo' => $tipo->tipo,
                    'afecta_sueldo' => $tipo->afecta_sueldo,
                    'porcentaje_remunerado' => $tipo->porcentaje_remunerado,
                    'requiere_documento' => $tipo->requiere_documento,
                    'acumula_asistencia' => $tipo->acumula_asistencia,
                    'fecha' => $fecha,
                    'total_asistidos' => $cantidad,
                ]);
            } else {
                // Si ya existe, solo actualizar el total
                $registro->update(['total_asistidos' => $cantidad]);
            }
        }
        $resumen->update([
            'total_planilla' => $total,
        ]);
    }
}