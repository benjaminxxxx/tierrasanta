<?php

namespace App\Services\Cuadrilla\TramoLaboral;

use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use App\Models\CuadTramoLaboral;
use App\Models\CuaGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Services\Cuadrilla\Reporte\RptCuadrillaPagosXTramo;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResumenTramoServicio
{
    public function procesarPago($resumenPorTramo, $listaPago, $periodo)
    {
        if ($resumenPorTramo->condicion == 'Pagado') {

            return $this->quitarPagosEnTramo($resumenPorTramo);
        }

        //Actualizar los estados de los registros diarios
        $codigoGrupo = $resumenPorTramo->grupo_codigo;
        $tramoLaboral = $resumenPorTramo->tramo;
        $this->actualizarRegistrosDiarios($listaPago, $periodo, $codigoGrupo, $tramoLaboral);
        $listaFiltrada = $this->filtrarListaPago($listaPago, $tramoLaboral->id);

        //
        //destruir el file excel
        $fileReporteExcel = $resumenPorTramo->excel_reporte_file;
        if ($fileReporteExcel) {
            $path = Storage::disk('public')->path($fileReporteExcel);
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $data = [
            'resumen_tramo' => $resumenPorTramo,
            'lista_pago' => $listaFiltrada,
            'fecha_inicio' => $resumenPorTramo->fecha_acumulada,
            'fecha_reporte' => now()->format('Y-m-d'),
            //'excel_reporte_file' => null,
        ];

        $rptCuadrillaPagoXTramo = new RptCuadrillaPagosXTramo();
        $ExcelResumenfile = $rptCuadrillaPagoXTramo->generarReporte($data);

        $resumenPorTramo->update([
            'condicion' => 'Pagado',
            'fecha' => Carbon::now()->format('Y-m-d'),
            'excel_reporte_file' => $ExcelResumenfile,
        ]);
        return $resumenPorTramo;
    }
    private function quitarPagosEnTramo($resumenPorTramo)
    {
        $registroTramoFuturo = CuadResumenPorTramo::where('tramo_acumulado_id', $resumenPorTramo->tramo_id)
            ->where('grupo_codigo', $resumenPorTramo->grupo_codigo)->first();
        //dd($resumenPorTramo->tramo_id, $resumenPorTramo->grupo_codigo,$registroTramoFuturo);
        //quitar los pagos en los registros diarios
        CuadRegistroDiario::where('tramo_pagado_jornal_id', $resumenPorTramo->tramo_id)
            ->where('codigo_grupo', [$resumenPorTramo->grupo_codigo])
            ->update([
                'esta_pagado' => false,
                'tramo_pagado_jornal_id' => null
            ]);
        CuadRegistroDiario::where('tramo_pagado_bono_id', $resumenPorTramo->tramo_id)
            ->where('codigo_grupo', [$resumenPorTramo->grupo_codigo])
            ->update([
                'bono_esta_pagado' => false,
                'tramo_pagado_bono_id' => null
            ]);

        //destruir el file excel
        $fileReporteExcel = $resumenPorTramo->excel_reporte_file;
        if ($fileReporteExcel) {
            $path = Storage::disk('public')->path($fileReporteExcel);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $resumenPorTramo->update([
            'condicion' => 'Pendiente',
            'fecha' => null,
            'excel_reporte_file' => null,
        ]);
        if ($registroTramoFuturo) {
            $registroTramoFuturo->update([
                'tramo_acumulado_id' => null,
            ]);
        }

        return $resumenPorTramo;
    }
    private function filtrarListaPago($listaPago, $tramoLaboralId)
    {
        return collect($listaPago)
            ->map(function ($personal) use ($tramoLaboralId) {
                $fechasFiltradas = collect($personal)
                    ->filter(function ($valores, $clave) use ($tramoLaboralId) {
                        if (!is_array($valores)) {
                            return true;
                        }

                        $costo = (float) ($valores['costo_dia'] ?? 0);
                        $bono = (float) ($valores['total_bono'] ?? 0);

                        $estaPagadoJornal = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $estaPagadoBono = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                        $jornalMismoTramo = ($valores['tramo_pagado_jornal_id'] ?? null) == $tramoLaboralId;
                        $bonoMismoTramo = ($valores['tramo_pagado_bono_id'] ?? null) == $tramoLaboralId;

                        // solo pasa si pertenece al tramo actual
                        return ($estaPagadoJornal && $costo > 0 && $jornalMismoTramo)
                            || ($estaPagadoBono && $bono > 0 && $bonoMismoTramo);
                    });

                $nuevo = $fechasFiltradas->mapWithKeys(function ($valores, $clave) use ($tramoLaboralId) {
                    if (!is_array($valores)) {
                        return [$clave => $valores];
                    }

                    $costo = (float) ($valores['costo_dia'] ?? 0);
                    $bono = (float) ($valores['total_bono'] ?? 0);

                    $estaPagadoJornal = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $estaPagadoBono = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $jornalMismoTramo = ($valores['tramo_pagado_jornal_id'] ?? null) == $tramoLaboralId;
                    $bonoMismoTramo = ($valores['tramo_pagado_bono_id'] ?? null) == $tramoLaboralId;

                    return [
                        $clave => [
                            'costo_dia' => ($estaPagadoJornal && $jornalMismoTramo) ? $costo : 0,
                            'total_bono' => ($estaPagadoBono && $bonoMismoTramo) ? $bono : 0,
                            'esta_pagado' => ($estaPagadoJornal && $jornalMismoTramo),
                            'bono_esta_pagado' => ($estaPagadoBono && $bonoMismoTramo),
                        ]
                    ];
                });

                return $nuevo;
            })
            ->filter(fn($personal) => collect($personal)->filter(fn($v) => is_array($v))->isNotEmpty())
            ->toArray();
    }
    private function actualizarRegistrosDiarios(array &$listaPago, array $periodo, string $codigoGrupo, $tramoLaboral)
    {
        // Agrupa todo en una transacción para consistencia
        DB::transaction(function () use (&$listaPago, $periodo, $codigoGrupo, $tramoLaboral) {
            // Recorremos la estructura por referencia para poder mutarla
            foreach ($listaPago as $cuadrilleroId => &$personal) {
                // salt safety: asegúrate que personal sea array
                if (!is_array($personal))
                    continue;

                foreach ($periodo as $fecha) {
                    $valores = $personal[$fecha] ?? null;
                    if (!$valores || !is_array($valores)) {
                        continue;
                    }

                    $estaPagado = filter_var($valores['esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $bonoPagado = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    // === Jornal ===
                    $jornal = CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('codigo_grupo', $codigoGrupo)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->where('costo_dia', '>', 0)
                        ->first();

                    if ($jornal) {
                        // regla: si tramo null o mismo tramo, permitimos actualizar
                        if (is_null($jornal->tramo_pagado_jornal_id) || $jornal->tramo_pagado_jornal_id == $tramoLaboral->id) {
                            // siempre actualizamos el booleano (puedes marcar o desmarcar)
                            $jornal->esta_pagado = $estaPagado ? 1 : 0;

                            // Si marcaste como pagado y antes era null -> asigna tramo
                            // Si desmarcaste y tramo era este tramo -> lo dejamos null
                            if ($estaPagado) {
                                if (is_null($jornal->tramo_pagado_jornal_id)) {
                                    $jornal->tramo_pagado_jornal_id = $tramoLaboral->id;
                                }
                            } else {
                                if ($jornal->tramo_pagado_jornal_id == $tramoLaboral->id) {
                                    $jornal->tramo_pagado_jornal_id = null;
                                }
                            }

                            $jornal->save();
                        }
                        // SIN IMPORTAR si actualizamos o no en BD, sincronizamos el array en memoria con el estado real en BD
                        $personal[$fecha]['esta_pagado'] = (bool) $jornal->esta_pagado;
                        $personal[$fecha]['tramo_pagado_jornal_id'] = $jornal->tramo_pagado_jornal_id;
                        // Aseguramos que el monto que mostramos venga de la BD
                        $personal[$fecha]['costo_dia'] = $jornal->costo_dia;
                    }

                    // === Bono ===
                    $bono = CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('codigo_grupo', $codigoGrupo)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->where('total_bono', '>', 0)
                        ->first();

                    if ($bono) {
                        if (is_null($bono->tramo_pagado_bono_id) || $bono->tramo_pagado_bono_id == $tramoLaboral->id) {
                            $bono->bono_esta_pagado = $bonoPagado ? 1 : 0;

                            if ($bonoPagado) {
                                if (is_null($bono->tramo_pagado_bono_id)) {
                                    $bono->tramo_pagado_bono_id = $tramoLaboral->id;
                                }
                            } else {
                                if ($bono->tramo_pagado_bono_id == $tramoLaboral->id) {
                                    $bono->tramo_pagado_bono_id = null;
                                }
                            }

                            $bono->save();
                        }

                        $personal[$fecha]['bono_esta_pagado'] = (bool) $bono->bono_esta_pagado;
                        $personal[$fecha]['tramo_pagado_bono_id'] = $bono->tramo_pagado_bono_id;
                        $personal[$fecha]['total_bono'] = $bono->total_bono;
                    }
                }
            }
            // liberar la referencia
            unset($personal);
        }); // end transaction
    }
    public function cambiarCondicion($resumenId)
    {
        $resumenPorTramo = CuadResumenPorTramo::findOrFail($resumenId);
        $condiciones = [
            'Pendiente' => 'Pagado',
            'Pagado' => 'Pendiente',
        ];
        $fechas = [
            'Pendiente' => Carbon::now()->format('Y-m-d'),
            'Pagado' => null,
        ];
        $resumenPorTramo->update([
            'condicion' => $condiciones[$resumenPorTramo->condicion],
            'fecha' => $fechas[$resumenPorTramo->condicion]
        ]);
        return $resumenPorTramo;
    }
    /**
     * Actualiza los campos fecha y recibo de un resumen.
     *
     * @param  int   $id
     * @param  array $data
     * @return CuadResumenPorTramo
     * @throws Exception
     */
    public static function actualizar(int $id, array $data): CuadResumenPorTramo
    {
        try {
            $resumen = CuadResumenPorTramo::findOrFail($id);

            // Prepara los datos para la actualización
            $updateData = [
                'fecha' => $data['fecha'] ?? $resumen->fecha,
                // [!] Aplica mb_strtoupper solo si se provee un nuevo recibo
                'recibo' => isset($data['recibo'])
                    ? mb_strtoupper($data['recibo'], 'UTF-8')
                    : $resumen->recibo,
            ];

            $resumen->update($updateData);

            return $resumen;
        } catch (Exception $e) {
            throw new Exception("Error al actualizar resumen ID [$id]: " . $e->getMessage());
        }
    }
    /**
     * Genera o actualiza el cuadro resumen para un tramo laboral específico.
     * Consolida sueldos y gastos adicionales, acumulando saldos pendientes de tramos anteriores.
     */
    public function generarResumen(int $tramoId, $fechaHastaBono): void
    {
        $tramoLaboral = CuadTramoLaboral::findOrFail($tramoId);
        $tramoLaboral->update([
            'fecha_hasta_bono' => $fechaHastaBono
        ]);

        $tramoAnterior = $this->encontrarAcumuladoAnterior($tramoLaboral);

        // 1. Obtenemos todos los resúmenes pendientes del tramo anterior.
        $resumenesAnteriores = $tramoAnterior
            ? CuadResumenPorTramo::where('tramo_id', $tramoAnterior->id)
                ->where('condicion', 'Pendiente')
                ->get()
            : collect();

        // 2. Obtenemos todos los grupos que tienen actividad en el tramo actual.
        $gruposEnTramoActual = $tramoLaboral->gruposEnTramos()
            ->with('grupo')
            ->orderBy('orden')
            ->get();
        // 3. (CORRECCIÓN CLAVE #1) Unificamos los códigos de grupo: tanto los que tienen
        // actividad actual como los que solo tienen deudas pendientes. Esto resuelve el problema
        // de que no se arrastraban deudas si no había actividad nueva.
        $codigosGrupoActuales = $gruposEnTramoActual->pluck('codigo_grupo');
        $codigosGrupoAnteriores = $resumenesAnteriores->pluck('grupo_codigo');
        $todosLosCodigos = $codigosGrupoActuales->merge($codigosGrupoAnteriores)->unique();

        $dataParaUpsert = [];
        $contadorAuxiliar = 0;
        // 4. Iteramos sobre la lista unificada de códigos de grupo.
        foreach ($todosLosCodigos as $codigoGrupo) {
            $contadorAuxiliar++;
            $grupoEnTramo = $gruposEnTramoActual->firstWhere('codigo_grupo', $codigoGrupo);
            $grupo = $grupoEnTramo ? $grupoEnTramo->grupo : CuaGrupo::where('codigo', $codigoGrupo)->first();

            if (!$grupo) {
                throw new Exception("Un grupo ya no existe no se puede genrar el resumen");
            }
            

            $resumenesAnterioresDelGrupo = $resumenesAnteriores->where('grupo_codigo', $codigoGrupo);

            // 🔹 Calcular sueldos
            $sueldos = $this->calcularSueldos($tramoLaboral, $resumenesAnterioresDelGrupo, $grupo, $codigoGrupo, $tramoAnterior);
            $dataParaUpsert = array_merge($dataParaUpsert, $sueldos);
/*if($contadorAuxiliar==1){
                dd($sueldos,$tramoLaboral, $resumenesAnterioresDelGrupo, $grupo, $codigoGrupo, $tramoAnterior);
            }*/
            // 🔹 Calcular adicionales
            $adicionales = $this->calcularAdicionales($tramoLaboral, $resumenesAnterioresDelGrupo, $grupo, $codigoGrupo, $tramoAnterior);
            $dataParaUpsert = array_merge($dataParaUpsert, $adicionales);

            // 🔹 Calcular bonos
            $bonos = $this->calcularBonos($tramoLaboral, $resumenesAnterioresDelGrupo, $grupo, $codigoGrupo, $tramoAnterior);
            $dataParaUpsert = array_merge($dataParaUpsert, $bonos);
        }
        // 1. Obtenemos las "claves únicas" de los registros que acabamos de calcular.
        // Una clave puede ser: "COD01-sueldo-ANDRES (septiembre)"
        $clavesCalculadas = collect($dataParaUpsert)->map(function ($row) {
            return $row['grupo_codigo'] . '-' . $row['tipo'] . '-' . $row['descripcion'];
        });
        // 2. Obtenemos los registros que ya existen en la BD para este tramo.
        $resumenesActualesEnDB = CuadResumenPorTramo::where('tramo_id', $tramoLaboral->id)->get();

        // 3. Identificamos los IDs de los registros que están en la BD pero NO en el nuevo cálculo.
        // Estos son los registros obsoletos que debemos eliminar.
        $idsParaEliminar = $resumenesActualesEnDB
            ->filter(function ($registroExistente) use ($clavesCalculadas) {
                $claveExistente = $registroExistente->grupo_codigo . '-' . $registroExistente->tipo . '-' . $registroExistente->descripcion;
                // Si la clave del registro de la BD no está en la lista de claves calculadas,
                // significa que debe ser eliminado.
                return !$clavesCalculadas->contains($claveExistente);
            })
            ->pluck('id');

        // 4. Si encontramos registros para eliminar, los borramos.
        if ($idsParaEliminar->isNotEmpty()) {
            CuadResumenPorTramo::destroy($idsParaEliminar);
        }

        $this->upsertResumenes($dataParaUpsert, $tramoLaboral->id);
    }

    /**
     * Calcula los sueldos para un grupo, considerando modalidad de pago y acumulados.
     */
    private function calcularSueldos($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior = null)
    {
        $costosQuery = CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
            ->whereBetween('fecha', [$tramoLaboral->fecha_inicio, $tramoLaboral->fecha_fin]);

        if ($grupo->modalidad_pago === 'mensual') {
            return $this->calcularSueldosMensuales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior, $costosQuery);
        }

        $totalCostosActual = $costosQuery->sum('costo_dia');
        //dd($totalCostosActual);
        $descripcion = $grupo->nombre;

        $registroAnterior = $resumenesAnteriores->firstWhere('descripcion', $descripcion);

        // La deuda pendiente es simplemente la deuda acumulada del registro anterior.
        $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;
        $deudaAcumuladaFinal = $deudaPendienteAnterior + $totalCostosActual;

        // Si no hay deuda nueva ni pendiente, no generamos un registro vacío.
        if ($deudaAcumuladaFinal == 0) {
            return [];
        }
        $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $tramoLaboral->fecha_inicio;
        return [
            [
                'grupo_codigo' => $codigoGrupo,
                'color' => $grupo->color,
                'tipo' => 'sueldo',
                'descripcion' => $descripcion,
                'condicion' => 'Pendiente',
                'fecha_acumulada' => $fechaAcumulada,
                'deuda_actual' => $totalCostosActual,
                'deuda_acumulada' => $deudaAcumuladaFinal, // Deuda anterior + actual
                'tramo_id' => $tramoLaboral->id,
                'tramo_acumulado_id' => $tramoAnterior?->id,
                'modalidad_pago' => $grupo->modalidad_pago,
                'fecha_inicio' => $tramoLaboral->fecha_inicio,
                'fecha_fin' => $tramoLaboral->fecha_fin,
            ]
        ];
    }
    private function calcularBonos($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior)
    {
        //los bonos no se pueden acumular de tramo en tramo, a veces se paga un sabado, dejado el viernes sin pagar del tramo anterior
        //entonces debo si hay tramo acumulado, luego consultar el tramo anterior si hay fecha_hasta_bono
        //si hay fecha_inicio seria desde esa fecha y fecha sin tampoco seria hasta fecha_fin sino seria hasta fecha_hasta_bono si es que es diferente de null
        //dd($tramoAnterior);
        $fechaDesde = $tramoLaboral->fecha_inicio;
        $fechaHasta = $tramoLaboral->fecha_hasta_bono ?? $tramoLaboral->fecha_fin;
        if ($tramoAnterior && $tramoAnterior->fecha_hasta_bono) {
            $fechaDesde = Carbon::parse($tramoAnterior->fecha_hasta_bono)->addDay();
        }

        $costosQuery = CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
        /*
                if ($grupo->modalidad_pago === 'mensual') {
                    return $this->calcularSueldosMensuales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior, $costosQuery);
                }*/

        $totalCostosActual = $costosQuery->sum('total_bono');
        $descripcion = 'BONO ' . $grupo->nombre;

        $registroAnterior = $resumenesAnteriores->firstWhere('descripcion', $descripcion);

        // La deuda pendiente es simplemente la deuda acumulada del registro anterior.
        $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;
        $deudaAcumuladaFinal = $deudaPendienteAnterior + $totalCostosActual;

        // Si no hay deuda nueva ni pendiente, no generamos un registro vacío.
        if ($deudaAcumuladaFinal == 0) {
            return [];
        }
        $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $fechaDesde;
        return [
            [
                'grupo_codigo' => $codigoGrupo,
                'color' => $grupo->color,
                'tipo' => 'bono',
                'descripcion' => $descripcion,
                'condicion' => 'Pendiente',
                'fecha_acumulada' => $fechaAcumulada,
                'deuda_actual' => $totalCostosActual,
                'deuda_acumulada' => $deudaAcumuladaFinal, // Deuda anterior + actual
                'tramo_id' => $tramoLaboral->id,
                'tramo_acumulado_id' => $tramoAnterior?->id,
                'modalidad_pago' => $grupo->modalidad_pago,
                'fecha_inicio' => $fechaDesde,
                'fecha_fin' => $fechaHasta,
            ]
        ];
    }
    /**
     * Lógica específica para el cálculo de sueldos con modalidad 'mensual'.
     * ESTA VERSIÓN ESTÁ CORREGIDA para arrastrar deudas de meses anteriores aunque no tengan actividad actual.
     */
    private function calcularSueldosMensuales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior, $costosQuery)
    {
        // 1. Pre-calculamos los costos del tramo actual, pero agrupados por el nombre del mes.
        // Ej: ['septiembre' => 180.42, 'octubre' => 360.84]
        $costosActualesPorMes = (clone $costosQuery)->get()
            ->groupBy(function ($registro) {
                // Agrupamos por el nombre del mes en español
                return Carbon::parse($registro->fecha)->translatedFormat('F');
            })
            ->map(function ($registrosDelMes) {
                // Sumamos el costo por cada mes
                return $registrosDelMes->sum('costo_dia');
            });

        // 2. Unificamos descripciones: las que vienen de deudas anteriores + las de costos actuales.
        $descripcionesAnteriores = $resumenesAnteriores->where('tipo', 'sueldo')->pluck('descripcion');
        $descripcionesNuevas = $costosActualesPorMes->keys()->map(fn($mes) => "{$grupo->nombre} ({$mes})");
        $todasLasDescripciones = $descripcionesAnteriores->merge($descripcionesNuevas)->unique()->values();

        $resultados = [];

        // 3. Iteramos sobre cada descripción (ej: "ANDRES (septiembre)", "ANDRES (octubre)").
        foreach ($todasLasDescripciones as $descripcion) {
            // Extraemos el nombre del mes de la descripción para buscar el costo actual.
            preg_match('/\((\p{L}+)\)/u', $descripcion, $matches); // \p{L}+ para letras unicode
            $mes = !empty($matches[1]) ? $matches[1] : null;

            // Buscamos el costo actual para ese mes (será 0 si no hubo actividad).
            $costoActual = $costosActualesPorMes->get($mes, 0);

            // Buscamos si había una deuda pendiente para esta descripción exacta.
            $registroAnterior = $resumenesAnteriores->firstWhere('descripcion', $descripcion);
            $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;

            // Calculamos la deuda final.
            $deudaAcumuladaFinal = $deudaPendienteAnterior + $costoActual;

            $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $tramoLaboral->fecha_inicio;

            if ($deudaAcumuladaFinal > 0) {
                $resultados[] = [
                    'grupo_codigo' => $codigoGrupo,
                    'color' => $grupo->color,
                    'tipo' => 'sueldo',
                    'descripcion' => $descripcion,
                    'condicion' => 'Pendiente',
                    'fecha_acumulada' => $fechaAcumulada,
                    'deuda_actual' => $costoActual,
                    'deuda_acumulada' => $deudaAcumuladaFinal,
                    'tramo_id' => $tramoLaboral->id,
                    'tramo_acumulado_id' => $tramoAnterior?->id,
                    'modalidad_pago' => $grupo->modalidad_pago,
                    'fecha_inicio' => $tramoLaboral->fecha_inicio,
                    'fecha_fin' => $tramoLaboral->fecha_fin,
                ];
            }
        }
        return $resultados;
    }

    /**
     * (CORRECCIÓN CLAVE #2) Calcula los gastos adicionales consolidando actuales con pendientes.
     * Esta función fue refactorizada para corregir el cálculo de la deuda y simplificar la lógica.
     */
    private function calcularAdicionales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior)
    {
        $gastosActuales = GastoAdicionalPorGrupoCuadrilla::where('cuad_tramo_laboral_id', $tramoLaboral->id)
            ->where('codigo_grupo', $codigoGrupo)
            ->get();

        $adicionalesAnteriores = $resumenesAnteriores->where('tipo', 'adicional');

        // Unificamos las descripciones de los gastos actuales y los pendientes.
        $descripcionesActuales = $gastosActuales->pluck('descripcion');
        $descripcionesAnteriores = $adicionalesAnteriores->pluck('descripcion');
        $todasLasDescripciones = $descripcionesActuales->merge($descripcionesAnteriores)->unique();

        $resultados = [];

        foreach ($todasLasDescripciones as $descripcion) {
            $montoActual = $gastosActuales->where('descripcion', $descripcion)->sum('monto');
            $registroAnterior = $adicionalesAnteriores->firstWhere('descripcion', $descripcion);

            // La deuda pendiente es la deuda acumulada del registro anterior.
            // Este es el cálculo correcto.
            $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;
            $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $tramoLaboral->fecha_inicio;

            // La nueva deuda acumulada es la suma de lo pendiente más lo actual.
            $deudaAcumuladaFinal = $deudaPendienteAnterior + $montoActual;

            if ($deudaAcumuladaFinal == 0)
                continue; // No generar registros en cero.

            $resultados[] = [
                'grupo_codigo' => $codigoGrupo,
                'color' => $grupo->color,
                'tipo' => 'adicional',
                'descripcion' => $descripcion,
                'condicion' => 'Pendiente',
                'fecha_acumulada' => $fechaAcumulada,
                'deuda_actual' => $montoActual,
                'deuda_acumulada' => $deudaAcumuladaFinal,
                'tramo_id' => $tramoLaboral->id,
                'tramo_acumulado_id' => $tramoAnterior?->id,
                'modalidad_pago' => $grupo->modalidad_pago,
                'fecha_inicio' => $tramoLaboral->fecha_inicio,
                'fecha_fin' => $tramoLaboral->fecha_fin,
            ];
        }

        return $resultados;
    }

    private function upsertResumenes(array $data, int $tramoId): void
    {
        if (empty($data)) {
            return;
        }

        $orden = 0;
        foreach ($data as $row) {

            // 1. Define los atributos que hacen único a un registro.
            $uniqueAttributes = [
                'tramo_id' => $tramoId,
                'grupo_codigo' => $row['grupo_codigo'],
                'descripcion' => $row['descripcion'],
                'tipo' => $row['tipo'],
            ];

            // 2. Busca si ya existe un registro con esa combinación única.
            $existingRecord = CuadResumenPorTramo::where($uniqueAttributes)->first();

            // 3. Prepara los datos que se van a guardar.
            $valuesToUpsert = $row;
            $valuesToUpsert['orden'] = ++$orden;

            // 4. [LA CLAVE] Si el registro ya existía, usamos sus valores guardados
            // para no sobrescribir los cambios manuales.
            if ($existingRecord) {
                $valuesToUpsert['condicion'] = $existingRecord->condicion;
            }

            // 5. Ejecuta la operación:
            // - Si no existía, crea un nuevo registro con todos los datos de $valuesToUpsert.
            // - Si ya existía, actualiza el registro pero ahora $valuesToUpsert contiene
            //   los valores de fecha, recibo y condicion que ya estaban en la base de datos.
            CuadResumenPorTramo::updateOrCreate($uniqueAttributes, $valuesToUpsert);
        }
    }

    /**
     * Encuentra el tramo laboral inmediatamente anterior al tramo actual.
     */
    private function encontrarAcumuladoAnterior(CuadTramoLaboral $tramo): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::where('fecha_fin', '<', $tramo->fecha_inicio)
            ->orderBy('fecha_fin', 'desc')
            ->first();
    }
}