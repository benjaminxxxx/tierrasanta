<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Exceptions\SplitSuspensionRequeridaException;
use App\Models\CampoCampania;
use App\Models\Labores;
use App\Models\PlanDetalleHora;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Models\PlanResumenDiarioTipoAsistencia;
use App\Models\PlanTipoAsistencia;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\Planilla\SincronizarSuspensionDesdeAsistenciaServicio;
use App\Services\PlanTipoAsistenciaServicio;
use App\Support\CalculoHelper;
use App\Support\FormatoHelper;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Carbon;

class PlanillaRegistroDiarioServicio
{

    public static function obtenerRegistrosMensualesConLicenciasConsiderados($mes, $anio)
    {
        return PlanRegistroDiario::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->where('asistencia', '!=', 'A')
            ->where('total_horas', '>', 0)
            ->with([])
            ->get()
            ->map(function ($rd) {

                // Cuando se tiene licencia es proque falto, y no hay forma de que tenga bonos
                $gastoBonoFdm = 0;

                return [
                    'fecha' => formatear_fecha($rd->fecha),
                    'plan_empleado_id' => $rd->detalleMensual?->plan_empleado_id,
                    'documento' => $rd->detalleMensual?->documento ?? 'S/D',
                    'empleado_nombre' => $rd->detalleMensual?->nombres,
                    'labor' => $rd->asistencia,
                    'campo' => '-',
                    'hora_inicio' => null,
                    'hora_salida' => null,
                    'total_horas' => (float) $rd->total_horas,
                    'gasto_bono' => round($gastoBonoFdm, 2),
                ];
            });
    }
    public static function obtenerRegistrosMensualesPorCampo($campo, $mes, $anio)
    {
        return PlanDetalleHora::whereHas('registroDiario', function ($q) use ($mes, $anio) {
            $q->whereMonth('fecha', $mes)->whereYear('fecha', $anio);
        })
            ->where('campo_nombre', $campo)
            ->with(['registroDiario.actividadesBonos.actividad', 'registroDiario.detalleMensual', 'labores'])
            ->get()
            ->map(function ($detalle) {
                $rd = $detalle->registroDiario;

                // Cálculo de duración del tramo en horas
                $inicio = Carbon::parse($detalle->hora_inicio);
                $fin = Carbon::parse($detalle->hora_fin);
                $horasDetalle = $inicio->diffInMinutes($fin) / 60;

                // Cálculo de Bonos específicos del campo FDM
                $gastoBonoFdm = $rd->actividadesBonos
                    ->where('actividad.campo', 'FDM')
                    ->sum('total_bono');

                return [
                    'fecha' => formatear_fecha($rd->fecha),
                    'plan_empleado_id' => $rd->detalleMensual?->plan_empleado_id,
                    'documento' => $rd->detalleMensual?->documento ?? 'S/D',
                    'empleado_nombre' => $rd->detalleMensual?->nombres,
                    'labor' => $detalle->labores?->nombre_labor ?? $detalle->codigo_labor,
                    'campo' => $detalle->campo_nombre,
                    'hora_inicio' => $detalle->hora_inicio,
                    'hora_salida' => $detalle->hora_fin,
                    'total_horas' => $horasDetalle,
                    //'costo_dia' => $rd->jornal_aplicado,
                    //'gasto' => round($gastoProrrateado, 2),
                    'gasto_bono' => round($gastoBonoFdm, 2),
                ];
            });
    }
    public function obtenerTotalHorasPorMes($mes, $anio)
    {
        // 1. Catálogo: { codigo => acumula_asistencia }
        $tiposAsistencia = PlanTipoAsistencia::get()
            ->pluck('acumula_asistencia', 'codigo')
            ->toArray();

        // 2. Traer TODOS los empleados del mes desde PlanMensualDetalle
        $empleados = PlanMensualDetalle::whereHas('planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)->where('anio', $anio);
        })
            ->get(['id', 'plan_empleado_id']); // id = plan_det_men_id

        // 3. Traer registros diarios del mes
        $diarios = PlanRegistroDiario::whereHas('detalleMensual.planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)->where('anio', $anio);
        })
            ->get();

        // Agrupar diarios por detalle mensual
        $diariosAgrupados = $diarios->groupBy('plan_det_men_id');

        // 4. Para cada empleado calcular horas y días
        $resultado = [];

        foreach ($empleados as $emp) {

            $registros = $diariosAgrupados->get($emp->id, collect());

            $totalHoras = 0;
            $totalHorasReales = 0;
            $conteo = 0;
            $faltasInjustificadas = 0;
            $totalBonoProductividad = 0;


            foreach ($registros as $r) {
                // Validar existencia en el catálogo

                if (!array_key_exists($r->asistencia, $tiposAsistencia)) {
                    dd($r);
                    throw new Exception("El tipo de asistencia '{$r->asistencia}' no está registrado.");
                }

                if ($tiposAsistencia[$r->asistencia] == 1) {
                    if ($r->asistencia == 'A') {
                        $totalHorasReales += $r->total_horas;
                    }
                    $totalHoras += $r->total_horas;
                    $totalBonoProductividad += $r->total_bono;
                    $conteo++;
                }
                $faltasInjustificadas += CalculoHelper::faltasInjustificadas($r->total_horas);
            }

            $resultado[$emp->plan_empleado_id] = [
                'plan_empleado_id' => $emp->plan_empleado_id,
                'horas_trabajadas' => $totalHoras,
                'horas_trabajadas_reales' => $totalHorasReales,
                'dias_trabajados' => $conteo,
                'faltas_injustificadas' => $faltasInjustificadas,
                'total_bono_productividad' => $totalBonoProductividad,
            ];
        }

        return $resultado;
    }

    /**
     * Sincroniza la asistencia diaria basada en un rango de fechas y un empleado.
     */
    public function actualizarAsistenciaPorRango($empleadoId, $fechaInicio, $fechaFin, $codigoAsistencia)
    {
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        $horasConsideradas = PlanTipoAsistenciaServicio::obtenerHorasConsideradas($codigoAsistencia);

        foreach ($periodo as $fecha) {

            if ($fecha->isSunday()) {
                continue;
            }

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
                        'total_horas' => $horasConsideradas
                    ]
                );
            }
        }
    }
    /*
    private function procesarDatos($datos, $totalActividades): array
    {
        $camposNormalizados = CampoServicio::obtenerMapaCamposNormalizados();
        $labores = Labores::pluck('codigo')->toArray();
        $datosProcesados = [];

        foreach ($datos as $i => $informacion) {
            $fila = $i + 1;
            $planillaMensualDetalleId = $informacion['plan_men_detalle_id'] ?? null;
            $asistencia = trim($informacion['asistencia'] ?? '');

            if (!$planillaMensualDetalleId) {
                continue;
            }

            $tramos = [];
            $sumaHorasTramos = 0;

            // Procesamiento de tramos/actividades
            for ($x = 1; $x <= $totalActividades; $x++) {
                $inicio = isset($informacion["entrada_$x"]) ? str_replace('.', ':', $informacion["entrada_$x"]) : null;
                $fin = isset($informacion["salida_$x"]) ? str_replace('.', ':', $informacion["salida_$x"]) : null;
                $labor = $informacion["labor_$x"] ?? null;
                $campo = $informacion["campo_$x"] ?? null;

                if (!$inicio && !$fin && !$campo && !$labor) {
                    continue;
                }

                if (!$inicio || !$fin || !$campo || !$labor) {
                    throw new Exception("Valores incompletos en fila {$fila}, tramo {$x}");
                }

                $campoKey = mb_strtolower($campo);
                if (!array_key_exists($campoKey, $camposNormalizados)) {
                    throw new Exception("El campo '{$campo}' en fila {$fila} no existe o no tiene alias.");
                }

                if (!in_array($labor, $labores)) {
                    throw new Exception("La labor '{$labor}' en fila {$fila} no existe.");
                }
                $inicio = FormatoHelper::normalizarHora($inicio);
                $fin = FormatoHelper::normalizarHora($fin);
                $hInicio = Carbon::parse($inicio);
                $hFin = Carbon::parse($fin);

                $horas = $hInicio->floatDiffInHours($hFin);

                $sumaHorasTramos += $horas;
                $tramos[] = [
                    'codigo_labor' => $labor,
                    'campo_nombre' => $camposNormalizados[$campoKey],
                    'hora_inicio' => $hInicio->format('H:i'),
                    'hora_fin' => $hFin->format('H:i'),
                ];
            }

            if (!empty($tramos) && trim($asistencia) != 'A') {
                throw new Exception("Si hay detalle, debe agregar un tipo de asistencia A.");
            }
            // --- Lógica de Negocio para Total Horas ---
            if ($asistencia === 'A') {
                if (empty($tramos)) {
                    throw new Exception("Debe agregar detalle si tiene asistencia en la fila {$fila}");
                }
                // Si es Asistencia, el total es la suma de los tramos
                $totalFinal = $sumaHorasTramos;
            } else {
                // Si NO es 'A', tomamos el total_horas que viene del input (o 0 si no existe)
                $totalFinal = $informacion['total_horas'] ?? 0;
            }

            $datosProcesados[] = [
                'plan_det_men_id' => $planillaMensualDetalleId,
                'asistencia' => $asistencia,
                'total_horas' => $totalFinal,
                'tramos' => $tramos
            ];
        }

        return $datosProcesados;
    }*/
    private function procesarDatos($datos, $totalActividades, $fecha): array
    {
        $camposNormalizados = CampoServicio::obtenerMapaCamposNormalizados();
        $labores = Labores::pluck('codigo')->toArray();
        $datosProcesados = [];
        $camposSinCampaniaVigente = []; // <-- acumulador, para reportar todos juntos

        $camposConCampaniaVigente = CampoCampania::where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fecha);
            })
            ->pluck('campo')
            ->flip();

        foreach ($datos as $i => $informacion) {
            $fila = $i + 1;
            $planillaMensualDetalleId = $informacion['plan_men_detalle_id'] ?? null;
            $asistencia = trim($informacion['asistencia'] ?? '');

            if (!$planillaMensualDetalleId) {
                continue;
            }

            $tramos = [];
            $sumaHorasTramos = 0;

            for ($x = 1; $x <= $totalActividades; $x++) {
                $inicio = isset($informacion["entrada_$x"]) ? str_replace('.', ':', $informacion["entrada_$x"]) : null;
                $fin = isset($informacion["salida_$x"]) ? str_replace('.', ':', $informacion["salida_$x"]) : null;
                $labor = $informacion["labor_$x"] ?? null;
                $campo = $informacion["campo_$x"] ?? null;

                if (!$inicio && !$fin && !$campo && !$labor) {
                    continue;
                }

                if (!$inicio || !$fin || !$campo || !$labor) {
                    throw new Exception("Valores incompletos en fila {$fila}, tramo {$x}");
                }

                $campoKey = mb_strtolower($campo);
                if (!array_key_exists($campoKey, $camposNormalizados)) {
                    throw new Exception("El campo '{$campo}' en fila {$fila} no existe o no tiene alias.");
                }

                if (!in_array($labor, $labores)) {
                    throw new Exception("La labor '{$labor}' en fila {$fila} no existe.");
                }

                $nombreCampoReal = $camposNormalizados[$campoKey];

                // Validación de campaña vigente para esta fecha
                $tieneCampaniaVigente = CampoCampania::where('campo', $nombreCampoReal)
                    ->where('fecha_inicio', '<=', $fecha)
                    ->where(function ($q) use ($fecha) {
                        $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fecha);
                    })
                    ->exists();

                // No entra al IF si existe campaña vigente O si el campo es 'FDM'
                if (!isset($camposConCampaniaVigente[$nombreCampoReal]) && $nombreCampoReal !== 'FDM') {
                    $camposSinCampaniaVigente[$nombreCampoReal] = true;
                    continue;
                }

                $inicio = FormatoHelper::normalizarHora($inicio);
                $fin = FormatoHelper::normalizarHora($fin);
                $hInicio = Carbon::parse($inicio);
                $hFin = Carbon::parse($fin);

                $horas = $hInicio->floatDiffInHours($hFin);

                $sumaHorasTramos += $horas;
                $tramos[] = [
                    'codigo_labor' => $labor,
                    'campo_nombre' => $nombreCampoReal,
                    'hora_inicio' => $hInicio->format('H:i'),
                    'hora_fin' => $hFin->format('H:i'),
                ];
            }

            if (!empty($tramos) && trim($asistencia) != 'A') {
                throw new Exception("Si hay detalle, debe agregar un tipo de asistencia A.");
            }

            if ($asistencia === 'A') {
                if (empty($tramos) && empty($camposSinCampaniaVigente)) {
                    throw new Exception("Debe agregar detalle si tiene asistencia en la fila {$fila}");
                }
                $totalFinal = $sumaHorasTramos;
            } else {
                $totalFinal = $informacion['total_horas'] ?? 0;
            }

            $datosProcesados[] = [
                'plan_det_men_id' => $planillaMensualDetalleId,
                'asistencia' => $asistencia,
                'total_horas' => $totalFinal,
                'tramos' => $tramos
            ];
        }

        // Se lanza al final, con TODOS los campos afectados en un solo mensaje
        if (!empty($camposSinCampaniaVigente)) {
            $campos = implode(', ', array_keys($camposSinCampaniaVigente));
            throw new Exception("Los siguientes campos {$campos} no tienen una campaña vigente dentro de la fecha.");
        }

        return $datosProcesados;
    }
    public function guardarRegistrosDiarios($fecha, $datos, $totalActividades)
    {
        $datosLimpios = $this->procesarDatos($datos, $totalActividades, $fecha);

        foreach ($datosLimpios as $item) {

            // Leer el estado ANTERIOR antes de sobrescribirlo, para detectar el cambio
            $registroAnterior = PlanRegistroDiario::where('plan_det_men_id', $item['plan_det_men_id'])
                ->where('fecha', $fecha)
                ->first();

            $asistenciaAnterior = $registroAnterior?->asistencia;

            $registro = PlanRegistroDiario::updateOrCreate(
                ['plan_det_men_id' => $item['plan_det_men_id'], 'fecha' => $fecha],
                ['asistencia' => $item['asistencia'], 'total_horas' => $item['total_horas']]
            );

            if ($asistenciaAnterior !== $item['asistencia']) {
                $planEmpleadoId = $registro->detalleMensual->plan_empleado_id; // ajustar según tu relación real

                app(SincronizarSuspensionDesdeAsistenciaServicio::class)->sincronizar(
                    $planEmpleadoId,
                    $fecha,
                    $asistenciaAnterior,
                    $item['asistencia'] ?: null
                );
            }

            if (empty($item['tramos'])) {
                $registro->detalles()->delete();
                if ($item['asistencia'] === '')
                    $registro->delete();
                continue;
            }

            $this->sincronizarTramos($registro, $item['tramos']);
        }

        $this->actualizarResumenAsistencia($fecha);
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

    private function actualizarResumenAsistencia($fecha)
    {
        $resumen = PlanResumenDiario::firstOrCreate(['fecha' => $fecha]);

        // 1️⃣ Recalcular desde BD (fuente real)
        $totales = PlanRegistroDiario::whereDate('fecha', $fecha)
            ->where('asistencia', '!=', '')
            ->selectRaw('asistencia, COUNT(*) as total')
            ->groupBy('asistencia')
            ->pluck('total', 'asistencia')
            ->toArray();

        $totalPlanilla = array_sum($totales);

        $codigosNuevos = array_keys($totales);

        // 2️⃣ eliminar los que ya no existen
        PlanResumenDiarioTipoAsistencia::where('plan_res_dia_id', $resumen->id)
            ->whereNotIn('codigo', $codigosNuevos)
            ->delete();

        foreach ($totales as $codigo => $cantidad) {

            $registro = PlanResumenDiarioTipoAsistencia::where([
                'plan_res_dia_id' => $resumen->id,
                'codigo' => $codigo,
                'fecha' => $fecha,
            ])->first();

            if (!$registro) {

                $tipo = app(PlanTipoAsistenciaServicio::class)->obtenerPorCodigo($codigo);
                if (!$tipo) {
                    continue;
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

                $registro->update([
                    'total_asistidos' => $cantidad
                ]);
            }
        }

        $resumen->update([
            'total_planilla' => $totalPlanilla
        ]);
    }
}