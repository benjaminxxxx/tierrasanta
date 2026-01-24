<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\Labores;
use App\Models\PlanDetalleHora;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Models\PlanResumenDiarioTipoAsistencia;
use App\Models\PlanTipoAsistencia;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\PlanTipoAsistenciaServicio;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Carbon;

class PlanillaRegistroDiarioServicio
{
    /**
     * Sincroniza la asistencia diaria basada en un rango de fechas y un empleado.
     */
    public function actualizarAsistenciaPorRango($empleadoId, $fechaInicio, $fechaFin, $codigoAsistencia)
    {
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        foreach ($periodo as $fecha) {
            $fechaString = $fecha->toDateString();

            // Buscamos el detalle mensual directamente cruzando con la cabecera (PlanMensual)
            // Esto es más eficiente que buscar por separado
            $detalleMensual = PlanMensualDetalle::whereHas('planillaMensual', function ($query) use ($fecha) {
                $query->where('mes', $fecha->month)
                    ->where('anio', $fecha->year);
            })
                ->where('plan_empleado_id', $empleadoId)
                ->first();

            if ($detalleMensual) {
                PlanRegistroDiario::updateOrCreate(
                    [
                        'plan_det_men_id' => $detalleMensual->id,
                        'fecha' => $fechaString,
                    ],
                    [
                        'asistencia' => $codigoAsistencia,
                        'total_horas' => 0 // Como es un permiso/periodo, horas trabajadas usualmente es 0
                    ]
                );
            }
        }
    }
    private function procesarDatos($datos, $totalActividades): array
    {
        $camposNormalizados = CampoServicio::obtenerMapaCamposNormalizados();
        $labores = Labores::pluck('codigo')->toArray();
        $datosProcesados = [];

        foreach ($datos as $i => $informacion) {
            $fila = $i + 1;
            $planillaMensualDetalleId = $informacion['plan_men_detalle_id'] ?? null;

            if (!$planillaMensualDetalleId)
                continue;

            $tramos = [];
            $totalHoras = 0;

            for ($x = 1; $x <= $totalActividades; $x++) {
                $inicio = isset($informacion["entrada_$x"]) ? str_replace('.', ':', $informacion["entrada_$x"]) : null;
                $fin = isset($informacion["salida_$x"]) ? str_replace('.', ':', $informacion["salida_$x"]) : null;
                $labor = $informacion["labor_$x"] ?? null;
                $campo = $informacion["campo_$x"] ?? null;

                if (!$inicio && !$fin && !$campo && !$labor)
                    continue;

                // Validación de integridad
                if (!$inicio || !$fin || !$campo || !$labor) {
                    throw new Exception("Valores incompletos en fila {$fila}, tramo {$x}");
                }

                // Validación de existencia y normalización
                $campoKey = mb_strtolower($campo);
                if (!array_key_exists($campoKey, $camposNormalizados)) {
                    throw new Exception("El campo '{$campo}' en fila {$fila} no existe o no tiene alias.");
                }

                if (!in_array($labor, $labores)) {
                    throw new Exception("La labor '{$labor}' en fila {$fila} no existe.");
                }

                $hInicio = Carbon::parse($inicio);
                $hFin = Carbon::parse($fin);
                $horas = $hInicio->floatDiffInHours($hFin);

                $totalHoras += $horas;
                $tramos[] = [
                    'codigo_labor' => $labor,
                    'campo_nombre' => $camposNormalizados[$campoKey],
                    'hora_inicio' => $hInicio->format('H:i'),
                    'hora_fin' => $hFin->format('H:i'),
                ];
            }

            $asistencia = trim($informacion['asistencia'] ?? '');
            if ($asistencia === 'A' && empty($tramos)) {
                throw new Exception("Debe agregar detalle si tiene asistencia en la fila {$fila}");
            }

            // Estructuramos el registro ya limpio para la persistencia
            $datosProcesados[] = [
                'plan_det_men_id' => $planillaMensualDetalleId,
                'asistencia' => $asistencia,
                'total_horas' => $totalHoras,
                'tramos' => $tramos
            ];
        }

        return $datosProcesados;
    }

    public function guardarRegistrosDiarios($fecha, $datos, $totalActividades)
    {
        // 1. Validar y normalizar (Si falla, lanza Exception y no guarda nada)
        $datosLimpios = $this->procesarDatos($datos, $totalActividades);
      
        $totalesPorAsistencia = [];

        foreach ($datosLimpios as $item) {
            // 2. Persistir Cabecera
            $registro = PlanRegistroDiario::updateOrCreate(
                ['plan_det_men_id' => $item['plan_det_men_id'], 'fecha' => $fecha],
                ['asistencia' => $item['asistencia'], 'total_horas' => $item['total_horas']]
            );

            // 3. Conteo de estadísticas
            if ($item['asistencia'] !== '') {
                $totalesPorAsistencia[$item['asistencia']] = ($totalesPorAsistencia[$item['asistencia']] ?? 0) + 1;
            }

            // 4. Manejo de tramos
            if (empty($item['tramos'])) {
                $registro->detalles()->delete();
                if ($item['asistencia'] === '')
                    $registro->delete();
                continue;
            }

            // 5. Sincronización optimizada
            $this->sincronizarTramos($registro, $item['tramos']);
        }

        if (!empty($totalesPorAsistencia)) {
            $this->actualizarResumenAsistencia($fecha, $totalesPorAsistencia);
        }
    }

    private function sincronizarTramos($registro, array $tramosNuevos)
    {
        $clave = fn($t) => "{$t['codigo_labor']}|{$t['campo_nombre']}|{$t['hora_inicio']}|{$t['hora_fin']}";

        $existentes = $registro->detalles()->get();
        $existentesMap = $existentes->keyBy(fn($e) => $clave($e->toArray()));
        $nuevosMap = collect($tramosNuevos)->keyBy($clave);

        // Eliminar los que ya no vienen
        foreach ($existentes as $ex) {
            if (!$nuevosMap->has($clave($ex->toArray())))
                $ex->delete();
        }

        // Crear los que no existen
        foreach ($nuevosMap as $key => $nuevo) {
            if (!$existentesMap->has($key)) {
                $registro->detalles()->create(array_merge($nuevo, ['orden' => 0])); // El orden se puede manejar por ID o index
            }
        }
    }
    /*
    public function guardarRegistrosDiarios($fecha, $datos, $totalActividades)
    {
        $errores = [];
        $totalesPorAsistencia = [];

        //Validar Datos

        $datos = $this->procesarDatos($datos);
dd($datos);
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
                    'total_horas' => $totalHoras
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
    } */
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

                $tipo = app(PlanTipoAsistenciaServicio::class)->obtenerPorCodigo($codigo);
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