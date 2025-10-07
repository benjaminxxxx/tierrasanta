<?php

namespace App\Services\Cuadrilla;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuadActividadBono;
use App\Models\CuadActividadProduccion;
use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadDetalleHora;
use App\Models\CuadGrupoCuadrilleroFecha;
use App\Models\CuadOrdenSemanal;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Models\Labores;
use App\Models\CuadGrupoOrden;
use App\Support\DateHelper;
use App\Support\FormatoHelper;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CuadrilleroServicio
{
    public static function obtenerListaGruposOrdenados($fechaInicio)
    {
        $grupos = self::sincronizarOrdenGruposSemana($fechaInicio);
        return $grupos;
    }
    public static function sincronizarOrdenGruposSemana($fechaInicio)
    {
        // Paso 1: obtener códigos de grupo desde registro diario
        $gruposEnUso = CuadOrdenSemanal::whereDate('fecha_inicio', $fechaInicio)
            ->distinct()
            ->pluck('codigo_grupo')
            ->filter()
            ->unique()
            ->values();

        if ($gruposEnUso->isEmpty()) {
            return collect(); // Nada que ordenar
        }

        // Paso 2: obtener registros existentes en orden
        $ordenesExistentes = CuadGrupoOrden::whereDate('fecha', $fechaInicio)->get();
        $codigosExistentes = $ordenesExistentes->pluck('codigo_grupo');

        // Paso 3: agregar los que faltan
        $maxOrden = $ordenesExistentes->max('orden') ?? 0;

        foreach ($gruposEnUso as $codigoGrupo) {
            if (!$codigosExistentes->contains($codigoGrupo)) {
                $maxOrden++;
                CuadGrupoOrden::create([
                    'fecha' => $fechaInicio,
                    'codigo_grupo' => $codigoGrupo,
                    'orden' => $maxOrden,
                ]);
            }
        }

        // Paso 4: eliminar los que ya no están en registro diario
        CuadGrupoOrden::whereDate('fecha', $fechaInicio)
            ->whereNotIn('codigo_grupo', $gruposEnUso)
            ->delete();

        // Paso 5: retornar la lista ordenada
        return CuadGrupoOrden::with('grupo')
            ->whereDate('fecha', $fechaInicio)
            ->orderBy('orden')
            ->get()
            ->map(function ($item) {
                return [
                    'codigo' => $item->codigo_grupo,
                    'nombre' => optional($item->grupo)->nombre,
                    'color' => optional($item->grupo)->color,
                    'orden' => $item->orden,
                    'costo_produccion' => 0
                ];
            })
            ->toArray();
    }

    public static function registrarPagos(array $listaPagos, $fechaInicio, $fechaFin)
    {
        $fechaInicio = Carbon::parse($fechaInicio);

        foreach ($listaPagos as $registro) {
            $cuadrilleroId = $registro['cuadrillero_id'];
            $codigoGrupo = $registro['codigo'];
            $pagos = $registro['pagos'];

            foreach ($pagos as $key => $monto) {
                // Validar que el key sea tipo 'jornal_1', 'jornal_2', etc.
                if (preg_match('/^jornal_(\d+)$/', $key, $matches)) {
                    $index = (int) $matches[1]; // número del jornal
                    $fecha = $fechaInicio->copy()->addDays($index - 1)->startOfDay();

                    $detalle = CuadRegistroDiario::whereDate('fecha', $fecha)
                        ->where('cuadrillero_id', $cuadrilleroId)
                        ->first();

                    if (!$detalle) {
                        throw new Exception("No se encontró registro para el cuadrillero ID $cuadrilleroId en fecha $fecha");
                    }

                    $detalle->esta_pagado = true;
                    $detalle->save();
                }
            }
        }
    }

    public static function obtenerHandsonTablePagoCuadrilla($fecha_inicio, $fecha_fin, $grupo = null, $nombre = null)
    {
        $inicio = Carbon::parse($fecha_inicio)->startOfDay();
        $fin = Carbon::parse($fecha_fin)->endOfDay();

        // Paso 1: Obtener fechas en orden
        $fechas = collect();
        for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
            $fechas->push($date->format('Y-m-d'));
        }

        $gruposPorDia = CuadGrupoCuadrilleroFecha::whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->keyBy(fn($g) => $g->cuadrillero_id . '|' . $g->fecha);

        $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
            ->with('cuadrillero')
            ->get()
            ->filter(function ($registro) use ($grupo, $nombre) {
                return (!$nombre || stripos($registro->cuadrillero->nombres ?? '', $nombre) !== false);
            });

        $registrosAgrupados = [];
        $totalesFila = [];

        foreach ($registros as $registro) {
            $fechaKey = $registro->fecha->format('Y-m-d');
            $grupoAsignado = $gruposPorDia[$registro->cuadrillero_id . '|' . $fechaKey]->codigo_grupo ?? 'SIN GRUPO';

            if ($grupo && $grupoAsignado !== $grupo) {
                continue;
            }

            $grupoKey = $registro->cuadrillero_id . '|' . $grupoAsignado;

            if (!isset($registrosAgrupados[$grupoKey])) {
                $registrosAgrupados[$grupoKey] = [
                    'codigo' => $grupoAsignado,
                    'cuadrillero_id' => $registro->cuadrillero_id,
                    'nombre_cuadrillero' => $registro->cuadrillero->nombres ?? '',
                    'total_jornal' => 0,
                    'total_bono' => 0,
                    'total' => 0
                ];
            }

            $fechaIndex = $fechas->search($fechaKey) + 1;
            $jornalKey = "jornal_{$fechaIndex}";
            $bonoKey = "bono_{$fechaIndex}";
            $pagadoKey = "pagado_{$fechaIndex}";

            $jornal = floatval($registro->costo_dia);
            $bono = floatval($registro->total_bono);
            $pagado = $registro->esta_pagado ? 1 : 0;

            $registrosAgrupados[$grupoKey][$jornalKey] = $jornal;
            $registrosAgrupados[$grupoKey][$bonoKey] = $bono;
            $registrosAgrupados[$grupoKey][$pagadoKey] = $pagado;

            // Sumar al subtotal
            $registrosAgrupados[$grupoKey]['total_jornal'] += $jornal;
            $registrosAgrupados[$grupoKey]['total_bono'] += $bono;
            $registrosAgrupados[$grupoKey]['total'] += $jornal + $bono;

            // Acumulamos en la fila de totales
            $totalesFila[$jornalKey] = ($totalesFila[$jornalKey] ?? 0) + $jornal;
            $totalesFila[$bonoKey] = ($totalesFila[$bonoKey] ?? 0) + $bono;
            $totalesFila[$pagadoKey] = ($totalesFila[$pagadoKey] ?? 0) + $pagado;
        }

        // Sumar totales generales
        $totalesFila['nombre_cuadrillero'] = 'TOTAL';
        $totalesFila['total_jornal'] = array_sum(array_filter($totalesFila, fn($v, $k) => str_starts_with($k, 'jornal_'), ARRAY_FILTER_USE_BOTH));
        $totalesFila['total_bono'] = array_sum(array_filter($totalesFila, fn($v, $k) => str_starts_with($k, 'bono_'), ARRAY_FILTER_USE_BOTH));
        $totalesFila['total'] = $totalesFila['total_jornal'] + $totalesFila['total_bono'];

        // Devolver
        $data = array_values($registrosAgrupados);
        $data[] = $totalesFila; // Fila TOTAL al final

        return $data;
    }

    public static function listarHandsontableGastosAdicionales($inicio, $fin)
    {
        return GastoAdicionalPorGrupoCuadrilla::with('grupo')
            ->whereBetween('fecha_gasto', [$inicio, $fin])
            ->get()
            ->map(function ($gasto) {
                return [
                    'grupo' => optional($gasto->grupo)->nombre, // relacion grupo
                    'descripcion' => $gasto->descripcion,
                    'fecha' => Carbon::parse($gasto->fecha_gasto)->format('Y-m-d'),
                    'monto' => $gasto->monto
                ];
            })->toArray();
    }

    public static function guardarGastosAdicionalesXGrupo($tramoId,$datos, $inicio, $fin)
    {
        $inicioCarbon = Carbon::parse($inicio)->startOfDay();
        $finCarbon =  Carbon::parse($fin)->endOfDay();

        // 1. Eliminar los existentes en el rango
        GastoAdicionalPorGrupoCuadrilla::whereBetween('fecha_gasto', [$inicioCarbon, $finCarbon])->delete();

        // 2. Insertar todos los nuevos
        foreach ($datos as $fila) {
            // Validar que exista el grupo por nombre
            $grupo = CuaGrupo::where('nombre', $fila['grupo'])->first();

            if (!$grupo) {
                continue;
            }

            // Convertir fecha
            $fecha = FormatoHelper::parseFecha($fila['fecha']);

            GastoAdicionalPorGrupoCuadrilla::create([
                'monto' => $fila['monto'],
                'descripcion' => $fila['descripcion'],
                'anio_contable' => Carbon::parse($fecha)->year,
                'mes_contable' => Carbon::parse($fecha)->month,
                'fecha_gasto' => $fecha,
                'cuad_tramo_laboral_id' => $tramoId,
                'codigo_grupo' => $grupo->codigo, // Usa la nueva columna
            ]);
        }
    }
    public static function guardarBonoCuadrilla($fila, $numeroRecojos, $actividadId)
    {
        $registroDiarioId = $fila['registro_diario_id'] ?? null;

        if (!$registroDiarioId) {
            throw new Exception("Falta el parámetro de identificación de reporte diario");
        }

        // Buscar o crear el registro de bono para esta actividad en este registro diario
        $actividadBono = CuadActividadBono::updateOrCreate(
            [
                'registro_diario_id' => $registroDiarioId,
                'actividad_id' => $actividadId
            ],
            [
                'total_bono' => $fila['total_bono'] ?? 0
            ]
        );

        // Eliminar producciones que ya no existen
        CuadActividadProduccion::where('actividad_bono_id', $actividadBono->id)
            ->where('numero_recojo', '>', $numeroRecojos)
            ->delete();

        // Guardar o actualizar producciones
        for ($i = 1; $i <= $numeroRecojos; $i++) {
            $produccion = $fila['produccion_' . $i] ?? null;

            if ($produccion) {
                CuadActividadProduccion::updateOrCreate(
                    [
                        'actividad_bono_id' => $actividadBono->id,
                        'numero_recojo' => $i
                    ],
                    [
                        'produccion' => $produccion
                    ]
                );
            } else {
                CuadActividadProduccion::where('actividad_bono_id', $actividadBono->id)
                    ->where('numero_recojo', $i)
                    ->delete();
            }
        }

        //Recalcular total_bono del registro diario sumando todos los bonos de sus actividades
        $sumaBonos = CuadActividadBono::where('registro_diario_id', $registroDiarioId)->sum('total_bono');

        $registroDiario = CuadRegistroDiario::findOrFail($registroDiarioId);
        $registroDiario->update([
            'total_bono' => $sumaBonos
        ]);
    }

    public static function guardarBonoCuadrillaobsoleta($fila, $fecha)
    {
        $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
        $campo = $fila['campo'] ?? null;
        $labor = $fila['labor'] ?? null;
        $totalBono = floatval($fila['total_bono'] ?? 0);

        // Buscar el registro diario para la fecha
        $registro = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
            ->whereDate('fecha', $fecha)
            ->first();

        if (!$registro) {
            return;
        }

        // Obtener detalles de esa actividad, ordenados por horario
        $detalles = $registro->detalleHoras()
            ->where('campo_nombre', $campo)
            ->where('codigo_labor', $labor)
            ->orderBy('hora_inicio')
            ->get();

        $conteoTramos = $detalles->count();

        if ($conteoTramos === 0) {
            return;
        }

        // Calcular bono proporcional por tramo
        $bonoPorTramo = round($totalBono / $conteoTramos, 2);

        // Recolectar solo los valores de producción válidos
        $producciones = [];
        for ($i = 1; $i <= $conteoTramos; $i++) {
            $produccionKey = "produccion_$i";
            $producciones[] = isset($fila[$produccionKey]) ? floatval($fila[$produccionKey]) : 0;
        }

        // Actualizar cada detalle con costo_bono y producción
        foreach ($detalles as $index => $detalle) {


            $detalle->update([
                'costo_bono' => $bonoPorTramo,
                'produccion' => $producciones[$index] ?? 0
            ]);
        }
    }

    public static function obtenerHandsontableRegistrosPorActividad($actividadId)
    {
        $actividad = Actividad::find($actividadId);
        if (!$actividad) {
            throw new Exception('No existe la actividad');
        }

        $campo_nombre = $actividad->campo;
        $codigo_labor = $actividad->codigo_labor;
        $fecha = $actividad->fecha;

        $registros = CuadRegistroDiario::with([
            'cuadrillero:id,nombres',
            'detalleHoras' => function ($query) use ($campo_nombre, $codigo_labor) {
                $query->where('campo_nombre', $campo_nombre)
                    ->where('codigo_labor', $codigo_labor)
                    ->orderBy('hora_inicio');
            }
        ])
            ->where('fecha', $fecha)
            ->whereHas('detalleHoras', function ($query) use ($campo_nombre, $codigo_labor) {
                $query->where('campo_nombre', $campo_nombre)
                    ->where('codigo_labor', $codigo_labor);
            })
            ->get();

        // 🟦 Obtener horarios únicos
        $horariosUnicos = collect();
        foreach ($registros as $r) {
            foreach ($r->detalleHoras as $d) {
                $inicio = \Carbon\Carbon::parse($d->hora_inicio)->format('H:i');
                $fin = \Carbon\Carbon::parse($d->hora_fin)->format('H:i');
                $horariosUnicos->push("$inicio-$fin");
            }
        }
        $horariosUnicos = $horariosUnicos->unique()->values()->slice(0, 10);

        // 🟩 Preparar filas
        $data = [];
        $maxTramos = 0;

        foreach ($registros as $r) {
            $row = [
                'cuadrillero_id' => $r->cuadrillero_id,
                'nombre_trabajador' => optional($r->cuadrillero)->nombres ?? '-',
                'campo' => $campo_nombre,
                'labor' => $codigo_labor,
                'total_bono' => 0,
            ];

            $bono = 0;
            $horariosConcatenados = [];

            foreach ($r->detalleHoras as $i => $d) {
                $inicio = \Carbon\Carbon::parse($d->hora_inicio)->format('H:i');
                $fin = \Carbon\Carbon::parse($d->hora_fin)->format('H:i');
                $key = "$inicio-$fin";

                $row["produccion_" . ($i + 1)] = $d->produccion ?? 0;
                $bono += $d->costo_bono ?? 0;
                $horariosConcatenados[] = $key;
            }

            $maxTramos = max($maxTramos, $r->detalleHoras->count());
            $row['horarios'] = implode(',', $horariosConcatenados);
            $row['rango_total_horas'] = DateHelper::calcularDuracionPorTramo($row['horarios']);
            $row['total_horas'] = DateHelper::calcularTotalHorasFloat($row['rango_total_horas']);
            $row['total_bono'] = $bono;

            $data[] = $row;
        }

        return [
            'data' => $data,
            'total_horarios' => $maxTramos,
        ];
    }

    public static function obtenerHandsontableReporte($fechaInicio, $fechaFin)
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();
        $coloresPorGrupo = CuaGrupo::pluck('color', 'codigo')->toArray();

        $dias = collect();
        for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
            $dias->push($date->copy());
        }

        $totalDias = $dias->count();

        $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
            ->with(['cuadrillero:id,nombres,dni,codigo_grupo'])
            ->get()
            ->groupBy('cuadrillero_id');

        $registrosPorOrden = CuadOrdenSemanal::whereDate('fecha_inicio', $inicio)
            ->with(['cuadrillero'])
            ->orderBy('codigo_grupo')
            ->orderBy('orden')
            ->get()
            ->keyBy('cuadrillero_id');
        $resultados = [];
        $totalTrabajadoresEseDia = [];
        $totalJornalEseDia = [];
        $totalBonoEseDia = [];

        self::sincronizarOrdenGruposSemana($fechaInicio);

        // Obtener orden de grupos para la semana
        $ordenGrupos = CuadGrupoOrden::where('fecha', $inicio->toDateString())
            ->orderBy('orden')
            ->pluck('orden', 'codigo_grupo')
            ->toArray();

        // Obtener los registros sin aplicar ordenamiento aún
        $registrosPorOrden = CuadOrdenSemanal::whereDate('fecha_inicio', $inicio)
            ->with(['cuadrillero'])
            ->get();

        // Ordenar primero por grupo según CuadGrupoOrden, luego por 'orden' interno del CuadOrdenSemanal
        $registrosOrdenados = $registrosPorOrden->sort(function ($a, $b) use ($ordenGrupos) {
            $ordenGrupoA = $ordenGrupos[$a->codigo_grupo] ?? 9999;
            $ordenGrupoB = $ordenGrupos[$b->codigo_grupo] ?? 9999;

            if ($ordenGrupoA === $ordenGrupoB) {
                // Mismo grupo: ordenar por orden interno
                return $a->orden <=> $b->orden;
            }

            return $ordenGrupoA <=> $ordenGrupoB;
        });

        // Si quieres conservar el acceso por cuadrillero_id como antes
        $registrosPorOrden = $registrosOrdenados->keyBy('cuadrillero_id');

        foreach ($registrosPorOrden as $cuadrilleroId => $registro) {

            $cuadrillero = $registro->cuadrillero;
            $registrosDiarios = $registros[$cuadrillero->id] ?? null;

            $fila = [
                'cuadrillero_id' => $cuadrilleroId,
                'cuadrillero_nombres' => $cuadrillero->nombres,
                'codigo_grupo' => $registro->codigo_grupo,
                'color' => $coloresPorGrupo[$registro->codigo_grupo] ?? '#FFFFFF'
            ];

            $grupoFijo = null;
            $totalCostos = 0;
            $totalBonos = 0;

            foreach ($dias as $index => $dia) {

                $fecha = $dia->toDateString();
                $registroHoras = null;

                if ($registrosDiarios) {
                    $registroHoras = $registrosDiarios->first(function ($item) use ($fecha) {
                        return optional($item->fecha)->toDateString() === $fecha;
                    });
                }

                $valorHoras = optional($registroHoras)->total_horas;
                $total_horas = ($valorHoras && $valorHoras > 0) ? $valorHoras : '-';

                $bono = ($registroHoras && $registroHoras->total_bono > 0) ? $registroHoras->total_bono : '-';
                $costo_dia = optional($registroHoras)->costo_dia;
                $costo_dia = ($costo_dia && $costo_dia > 0) ? $costo_dia : '-';

                // Agregar columnas planas
                $fila["dia_" . ($index + 1)] = $total_horas;
                $fila["jornal_" . ($index + 1)] = $costo_dia;
                $fila["bono_" . ($index + 1)] = $bono;

                // Totales
                $totalTrabajadoresEseDia[$index + 1] = $totalTrabajadoresEseDia[$index + 1] ?? 0;
                $totalJornalEseDia[$index + 1] = $totalJornalEseDia[$index + 1] ?? 0;
                $totalBonoEseDia[$index + 1] = $totalBonoEseDia[$index + 1] ?? 0;

                if ((float) $total_horas > 0) {
                    $totalTrabajadoresEseDia[$index + 1]++;
                }
                if ((float) $costo_dia > 0) {
                    $totalJornalEseDia[$index + 1] += (float) $costo_dia;
                }
                if ((float) $bono > 0) {
                    $totalBonoEseDia[$index + 1] += (float) $bono;
                }

                $totalCostos += (float) $costo_dia;
                $totalBonos += (float) $bono;
            }

            $fila['total_costo'] = $totalCostos + $totalBonos;
            $fila['total_bono'] = $totalBonos;

            $resultados[] = $fila;
        }

        $filaTotales = [
            'cuadrillero_id' => null,
            'cuadrillero_nombres' => 'TOTALES',
        ];
        foreach ($dias as $index => $dia) {
            $filaTotales["dia_" . ($index + 1)] = $totalTrabajadoresEseDia[$index + 1] ?? 0;
            $filaTotales["jornal_" . ($index + 1)] = $totalJornalEseDia[$index + 1] ?? 0;
            $filaTotales["bono_" . ($index + 1)] = $totalBonoEseDia[$index + 1] ?? 0;
        }

        $filaTotales['total_costo'] = collect($resultados)->sum('total_costo');

        $resultados[] = $filaTotales;

        // 🟠 Generar headers planos
        $headers = [];

        $diasSemana = ['D', 'L', 'M', 'M', 'J', 'V', 'S']; // empieza en domingo
        foreach ($dias as $d) {
            $headers[] = $diasSemana[$d->dayOfWeek] . '<br/>' . $d->day;
        }
        foreach ($dias as $d) {
            $headers[] = $diasSemana[$d->dayOfWeek] . '<br/>' . $d->day;
        }
        foreach ($dias as $d) {
            $headers[] = 'B<br/>' . $d->day;
        }
        $headers[] = "Total días";
        $headers[] = "Total costos";
        $headers[] = "Total bonos";

        return [
            'data' => $resultados,
            'headers' => $headers,
            'total_dias' => $totalDias
        ];
    }

    /*
    public static function obtenerHandsontableReporte($fechaInicio, $fechaFin)
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();
        $coloresPorGrupo = CuaGrupo::pluck('color', 'codigo')->toArray();

        $dias = collect();
        for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
            $dias->push($date->copy());
        }

        $totalDias = $dias->count();

        // 🟠 1. Registros de asistencia
        $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
            ->with(['cuadrillero:id,nombres,dni,codigo_grupo'])
            ->get()
            ->groupBy('cuadrillero_id');

        // 🟠 2. Relación de grupo personalizado por día
        $grupoPorFecha = CuadGrupoCuadrilleroFecha::whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->groupBy(fn($r) => $r->cuadrillero_id . '|' . $r->fecha);

        $resultados = [];

        foreach ($registros as $cuadrilleroId => $items) {

            $cuadrillero = $items->first()->cuadrillero;

            // 🟠 Chequear solo la PRIMERA FECHA del rango
            $fechaPrimera = $inicio->toDateString();
            $tieneGrupoAsignado = CuadGrupoCuadrilleroFecha::where('cuadrillero_id', $cuadrilleroId)
                ->where('fecha', $fechaPrimera)
                ->exists();

            // Si NO tiene asignación y tiene grupo predeterminado
            if (!$tieneGrupoAsignado && $cuadrillero->codigo_grupo) {

                self::asignarGrupoPeriodo(
                    $cuadrilleroId,
                    $cuadrillero->codigo_grupo,
                    $inicio->toDateString(),
                    $fin->toDateString()
                );

                // volver a cargar SOLO una vez para que incluya lo nuevo
                $grupoPorFecha = CuadGrupoCuadrilleroFecha::whereBetween('fecha', [$inicio, $fin])
                    ->get()
                    ->groupBy(fn($r) => $r->cuadrillero_id . '|' . $r->fecha);
            }

            $fila = [
                'cuadrillero_id' => $cuadrilleroId,
                'cuadrillero_nombres' => $cuadrillero->nombres,
                'codigo_grupo' => null,
            ];

            $grupoFijo = null;
            $totalCostos = 0;
            $totalBonos = 0;

            foreach ($dias as $index => $dia) {
                $fecha = $dia->toDateString();

                $key = $cuadrilleroId . '|' . $fecha;

                $registro = $items->first(function ($item) use ($fecha) {
                    return optional($item->fecha)->toDateString() === $fecha;
                });

                $valorHoras = optional($registro)->total_horas;
                $total_horas = ($valorHoras && $valorHoras > 0) ? $valorHoras : '-';

                $bono = optional($registro)->total_bono ?? 0;
                $costo_dia = optional($registro)->costo_dia;
                $costo_dia = ($costo_dia && $costo_dia > 0) ? $costo_dia : '-';

                // Grupo del día
                $grupo = $grupoPorFecha[$key][0]->codigo_grupo ?? null;

                $grupoFijo ??= $grupo;

                // Agregar columnas planas
                $fila["dia_" . ($index + 1)] = $total_horas;
                $fila["jornal_" . ($index + 1)] = $costo_dia;
                $fila["bono_" . ($index + 1)] = $bono;

                // Totales
                $totalCostos += (float) $costo_dia;
                $totalBonos += $bono;
            }

            $grupoAsignado = $grupoFijo ?? 'SIN GRUPO';
            $fila['codigo_grupo'] = $grupoAsignado;
            $fila['color'] = $coloresPorGrupo[$grupoAsignado] ?? '#FFFFFF';
            $fila['total_costo'] = $totalCostos;
            $fila['total_bono'] = $totalBonos;

            $resultados[] = $fila;
        }

        // 🟠 Generar headers planos
        $headers = [];

        $diasSemana = ['D', 'L', 'M', 'M', 'J', 'V', 'S']; // empieza en domingo
        foreach ($dias as $d) {
            $headers[] = $diasSemana[$d->dayOfWeek] . '<br/>' . $d->day;
        }
        foreach ($dias as $d) {
            $headers[] = $diasSemana[$d->dayOfWeek] . '<br/>' . $d->day;
        }
        foreach ($dias as $d) {
            $headers[] = 'B<br/>' . $d->day;
        }
        $headers[] = "Total días";
        $headers[] = "Total costos";
        $headers[] = "Total bonos";

        // 🟠 ORDENAR POR ORDEN SEMANAL
        $inicioSemana = Carbon::parse($fechaInicio)->startOfWeek()->format('Y-m-d');

        $ordenSemanal = CuadOrdenSemanal::where('fecha_inicio', $inicioSemana)
            ->orderBy('orden')
            ->pluck('orden', 'cuadrillero_id')
            ->toArray();
        //dd($ordenSemanal);
        $resultadosById = collect($resultados)->keyBy('cuadrillero_id');

        $ordenados = [];
        foreach ($ordenSemanal as $cuadrilleroId => $orden) {
            if (isset($resultadosById[$cuadrilleroId])) {
                $ordenados[] = $resultadosById[$cuadrilleroId];
            }
        }

        // Agregar al final los que no tengan orden guardado (nuevos)
        foreach ($resultadosById as $id => $fila) {
            if (!isset($ordenSemanal[$id])) {
                $ordenados[] = $fila;
            }
        }

        return [
            'data' => $ordenados,
            'headers' => $headers,
            'total_dias' => $totalDias,
        ];
    }
*/
    public static function asignarGrupoPeriodo(int $cuadrilleroId, string $codigoGrupo, string $fechaInicio, string $fechaFin): void
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            CuadGrupoCuadrilleroFecha::updateOrCreate(
                [
                    'cuadrillero_id' => $cuadrilleroId,
                    'fecha' => $fecha->toDateString(),
                ],
                [
                    'codigo_grupo' => $codigoGrupo,
                ]
            );
        }
    }


    public static function guardarCostosDiariosGrupo(array $datos, string $fechaInicio): void
    {
        $fechaInicio = Carbon::parse($fechaInicio);

        foreach ($datos as $grupo) {
            $codigo = $grupo['codigo_grupo'] ?? null;
            if (!$codigo)
                continue;

            for ($i = 1; array_key_exists("dia_$i", $grupo); $i++) {
                $valor = (float)$grupo["dia_$i"];
                $fecha = $fechaInicio->copy()->addDays($i - 1)->toDateString();

                if (is_numeric($valor) && $valor > 0) {
                    // Crear o actualizar si hay un valor válido
                    CuadCostoDiarioGrupo::updateOrCreate(
                        [
                            'codigo_grupo' => $codigo,
                            'fecha' => $fecha,
                        ],
                        [
                            'jornal' => $valor,
                        ]
                    );
                } else {
                    // Eliminar si no hay valor válido
                    CuadCostoDiarioGrupo::where('codigo_grupo', $codigo)
                        ->where('fecha', $fecha)
                        ->delete();
                }
            }
        }
    }

    public static function guardarPrecioSugerido(array $datos): void
    {
        foreach ($datos as $grupo) {
            $codigo = $grupo['codigo_grupo'] ?? null;
            $nuevoSugerido = $grupo['costo_dia_sugerido'] ?? null;

            if (!$codigo || !is_numeric($nuevoSugerido)) {
                continue;
            }

            CuaGrupo::where('codigo', $codigo)
                ->update([
                    'costo_dia_sugerido' => $nuevoSugerido
                ]);
        }
    }

    public static function obtenerHandsontableCostosAsignados($tramoLaboralId)
    {
        
        $tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoLaboralId);
        if(!$tramoLaboral){
            return [];
        }
        $fechaInicio = $tramoLaboral->fecha_inicio;
        $fechaFin = $tramoLaboral->fecha_fin;
        
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        // 1️⃣ Fechas en rango
        $dias = collect();
        for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
            $dias->push($date->toDateString());
        }

        // 2️⃣ Obtener todos los costos registrados en el rango
        $costosGuardados = CuadCostoDiarioGrupo::whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->groupBy(fn($item) => $item->codigo_grupo);

        // 3️⃣ Obtener TODOS los grupos activos (o incluso inactivos si se usaron)
        $gruposUsadosEnCostos = $tramoLaboral->gruposEnTramos()->pluck('codigo_grupo')->toArray();
        
        

        // Incluye también grupos activos aunque no tengan costos aún
        

        $gruposFinales = CuaGrupo::whereIn('codigo', $gruposUsadosEnCostos)->get();

        $result = collect();
        $total_dias = 0;

        foreach ($gruposFinales as $grupo) {
            
            if (!$grupo)
                continue;

            $total_dias = $dias->count();

            $item = [
                'codigo_grupo' => $grupo->codigo,
                'nombre' => $grupo->nombre,
                'color' => $grupo->color,
                'modalidad_pago' => $grupo->modalidad_pago,
                'costo_dia_sugerido' => $grupo->costo_dia_sugerido,
                'estado' => $grupo->estado
            ];

            $index = 1;
            foreach ($dias as $dia) {
                $valor = optional(
                    $costosGuardados->get($grupo->codigo)?->firstWhere('fecha', $dia)
                )->jornal;

                $item["dia_{$index}"] = $valor;
                $index++;
            }

            $result->push($item);
        }


        // Generar headers legibles: ["Lun 11", "Mar 12", ...]
        $headers = collect();
        foreach ($dias as $dia) {
            $label = Carbon::parse($dia)
                ->locale('es')
                ->isoFormat('ddd D'); // Ejemplo: "lun. 11"

            // Limpio y capitalizo: "Lun 11"
            $label = ucfirst(str_replace('.', '', $label));

            $headers->push($label);
        }

        return [
            'data' => $result,
            'total_dias' => $total_dias,
            'headers' => $headers,
        ];
    }
    /**
     * Crea o actualiza un cuadrillero.
     *
     * @param array $data
     * @param int|null $cuadrilleroId
     * @return Cuadrillero
     * @throws QueryException
     */
    public static function guardarCuadrillero(array $data, $cuadrilleroId = null)
    {
        return DB::transaction(function () use ($data, $cuadrilleroId) {
            if ($cuadrilleroId) {
                $cuadrillero = Cuadrillero::findOrFail($cuadrilleroId);
                $cuadrillero->nombres = mb_strtoupper($data['nombres']);
                $cuadrillero->dni = $data['dni'];
                $cuadrillero->codigo_grupo = $data['codigo_grupo'] ?? null;
                $cuadrillero->save();
            } else {
                $cuadrillero = new Cuadrillero([
                    'nombres' => mb_strtoupper($data['nombres']),
                    'dni' => $data['dni'],
                    'codigo_grupo' => $data['codigo_grupo'] ?? null,
                ]);
                $cuadrillero->save();
            }

            return $cuadrillero;
        });
    }
    public static function obtenerGrupos()
    {
        return CuaGrupo::where('estado', true)->with(['cuadrilleros'])->get();
    }
    public static function calcularCostosCuadrilla($inicio, $fin = null)
    {
        $inicioDate = Carbon::parse($inicio)->startOfDay();
        $finDate = $fin ? Carbon::parse($fin)->endOfDay() : $inicioDate->copy()->endOfDay();
      

        $registroDiarioCuadrilla = CuadRegistroDiario::whereBetween('fecha', [$inicioDate, $finDate])
        ->with(['grupo'])
        ->get();
        $costosDiariosDuranteFechas = CuadCostoDiarioGrupo::whereBetween('fecha', [$inicioDate, $finDate])->get();

        foreach ($registroDiarioCuadrilla as $asistenciaCuadrillero) {
            $totalHoras = (float) $asistenciaCuadrillero->total_horas;
            $codigoGrupo = $asistenciaCuadrillero->grupo;

            if ($totalHoras <= 0) {
                $asistenciaCuadrillero->costo_dia = 0;
                // Actualizar también total_bono en cero
                //$asistenciaCuadrillero->total_bono = 0; calculado en otro sitio
                $asistenciaCuadrillero->save();
                continue;
            }

            $fechaStr = $asistenciaCuadrillero->fecha instanceof \Carbon\Carbon
                ? $asistenciaCuadrillero->fecha->toDateString()
                : (string) $asistenciaCuadrillero->fecha;


            if (!$codigoGrupo) {
                $asistenciaCuadrillero->costo_dia = 0;
                //$asistenciaCuadrillero->total_bono = 0;  calculado en otro sitio
                $asistenciaCuadrillero->save();
                continue;
            }

            $costoDiario = $costosDiariosDuranteFechas
                ->where('fecha', $fechaStr)
                ->where('codigo_grupo', $asistenciaCuadrillero->codigo_grupo)
                ->first();

            $costoEseDiaX8Horas = 0;
            if ($costoDiario) {
                $costoEseDiaX8Horas = (float) $costoDiario->jornal;
            }

            if ($asistenciaCuadrillero->costo_personalizado_dia !== null) {
                $costoEseDiaX8Horas = (float) $asistenciaCuadrillero->costo_personalizado_dia;
            }

            if ($costoEseDiaX8Horas <= 0) {
                $asistenciaCuadrillero->costo_dia = 0;
                //$asistenciaCuadrillero->total_bono = 0;  calculado en otro sitio
                $asistenciaCuadrillero->save();
                continue;
            }

            $costo_dia = ($totalHoras / 8) * $costoEseDiaX8Horas;
            $asistenciaCuadrillero->costo_dia = round($costo_dia, 2);

            $asistenciaCuadrillero->save();
        }
    }

    /**
     * registrarOrdenSemanal
     *
     * Función para registrar el orden semanal de cuadrilleros para reportes.
     * Esta función procesa una lista de cuadrilleros, asigna su orden para la semana indicada
     * y actualiza la tabla cuad_orden_semanal de forma limpia y consistente.
     *
     * - Borra los registros anteriores de esa semana antes de guardar.
     * - Filtra los registros sin nombre (que representan eliminados por el usuario).
     * - Verifica y normaliza los IDs de cuadrilleros: corrige cambios de nombre o crea nuevos registros.
     * - Agrupa por grupo de trabajo, preservando el orden original dentro del grupo.
     * - Asigna un orden incremental, dejando los "SIN GRUPO" al final.
     * - Inserta en la tabla de orden semanal.
     *
     * @param  string $fechaInicio  Fecha (cualquier día de la semana), se usará su lunes como clave
     * @param  array  $rows         Array de cuadrilleros con datos (id, nombre, grupo, etc.)
     * @return array                Lista final normalizada y con campo 'orden' asignado
     */
    public static function registrarOrdenSemanal($fechaInicio, $codigo, $rows)
    {
        $orden = 0;


        //Registrar Orden del grupo

        $inicio = Carbon::parse($fechaInicio);
        $dias = collect();
        for ($i = 0; $i < 7; $i++) {
            $dias->push($inicio->copy()->addDays($i));
        }

        // Obtener los IDs de cuadrilleros que se van a registrar
        $cuadrilleroIds = collect($rows)->pluck('cuadrillero_id')->filter()->unique()->toArray();

        // Eliminar registros que ya no están en el nuevo orden
        CuadOrdenSemanal::whereDate('fecha_inicio', $fechaInicio)
            ->where('codigo_grupo', $codigo)
            ->whereNotIn('cuadrillero_id', $cuadrilleroIds)
            ->delete();

        // Eliminar del grupo anterior en los 7 días si el cuadrillero ya no pertenece
        $fechas = $dias->map(fn($d) => $d->toDateString());

        CuadGrupoCuadrilleroFecha::whereIn('fecha', $fechas)
            ->where('codigo_grupo', $codigo)
            ->whereNotIn('cuadrillero_id', $cuadrilleroIds)
            ->delete();

        // 1. Obtener los cuadrilleros que estaban en este grupo en esas fechas
        $cuadrillerosAntiguos = CuadGrupoCuadrilleroFecha::whereIn('fecha', $fechas)
            ->where('codigo_grupo', $codigo)
            ->pluck('cuadrillero_id')
            ->unique()
            ->toArray();

        // 2. Determinar cuáles de esos ya no están en la nueva lista
        $cuadrillerosAEliminar = array_diff($cuadrillerosAntiguos, $cuadrilleroIds);

        // 3. Eliminar solo registros de esos cuadrilleros en esas fechas
        CuadRegistroDiario::whereIn('fecha', $fechas)
            ->whereIn('cuadrillero_id', $cuadrillerosAEliminar)
            ->delete();

        // Insertar o actualizar el orden y grupo en los 7 días
        foreach ($rows as $row) {
            $orden++;
            $cuadrilleroId = $row['cuadrillero_id'];

            CuadOrdenSemanal::updateOrCreate(
                [
                    'cuadrillero_id' => $cuadrilleroId,
                    'fecha_inicio' => $fechaInicio,
                ],
                [
                    'codigo_grupo' => $codigo,
                    'orden' => $orden,
                ]
            );

            // Asegurar grupo asignado en los 7 días
            foreach ($dias as $d) {
                CuadGrupoCuadrilleroFecha::updateOrCreate(
                    [
                        'cuadrillero_id' => $cuadrilleroId,
                        'fecha' => $d->toDateString(),
                    ],
                    [
                        'codigo_grupo' => $codigo,
                    ]
                );
            }
        }

        return $rows;
    }

  

    /**
     * guardarReporteSemanal
     *
     * Procesa y guarda el detalle semanal de cuadrilleros.
     * - Usa la lista limpia y ordenada (de registrarOrdenSemanal) con IDs consistentes.
     * - Asigna los grupos diarios para toda la semana.
     * - Inserta registros diarios base si no existen.
     * - Actualiza asistencias con las horas de trabajo.
     *
     * NOTA: Ya no necesita manejar eliminaciones por nombre vacío,
     * porque registrarOrdenSemanal ya filtra esos casos.
     *
     * @param string $inicio  Fecha de inicio (lunes) de la semana
     * @param string $fin     Fecha de fin (domingo) de la semana
     * @param array  $rows    Lista de cuadrilleros con datos de trabajo y orden
     * @return bool
     * @throws \Throwable
     */

    public static function guardarReporteSemanal($inicio, $fin, $rows)
    {
        DB::beginTransaction();
        try {

            // ✅ 2. Calcular rango de fechas
            $inicioDate = Carbon::parse($inicio)->startOfDay();
            $finDate = Carbon::parse($fin)->endOfDay();

            $dias = collect();
            for ($date = $inicioDate->copy(); $date->lte($finDate); $date->addDay()) {
                $dias->push($date->copy());
            }

            // ✅ 3. Obtener los IDs actuales procesados (de orden semanal)
            $cuadrilleroIdsActuales = collect($rows)->pluck('cuadrillero_id')->unique()->filter()->values();

            // ✅ 4. Buscar los cuadrilleros que ya estaban en CuadRegistroDiario pero que han sido eliminados del nuevo orden
            $cuadrillerosAEliminar = CuadRegistroDiario::whereBetween('fecha', [$inicioDate, $finDate])
                ->whereNotIn('cuadrillero_id', $cuadrilleroIdsActuales)
                ->pluck('cuadrillero_id')
                ->unique();

            foreach ($cuadrillerosAEliminar as $cuadrilleroId) {
                // Buscar todos sus registros diarios de la semana
                $registros = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                    ->whereBetween('fecha', [$inicioDate, $finDate])
                    ->get();

                $tieneDetalle = false;
                foreach ($registros as $registro) {
                    $existeDetalle = CuadDetalleHora::where('registro_diario_id', $registro->id)->exists();
                    if ($existeDetalle) {
                        $tieneDetalle = true;
                        break;
                    }
                }

                if (!$tieneDetalle) {
                    // ✅ No tiene detalle → eliminar los registros
                    CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                        ->whereBetween('fecha', [$inicioDate, $finDate])
                        ->delete();

                    CuadGrupoCuadrilleroFecha::where('cuadrillero_id', $cuadrilleroId)
                        ->whereBetween('fecha', [$inicioDate, $finDate])
                        ->delete();
                }
            }

            // ✅ 5. Procesar cada fila de la nueva lista
            foreach ($rows as $i => $fila) {
                //$nombre = trim(mb_strtoupper($fila['cuadrillero_nombres']) ?? '');
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                $codigoGrupo = trim($fila['codigo_grupo'] ?? null);
                $codigoGrupo = $codigoGrupo === 'SIN GRUPO' ? null : $codigoGrupo;

                if (!$cuadrilleroId) {
                    continue;
                }

                // ✅ Asignar grupo por cada día
                if ($codigoGrupo) {
                    foreach ($dias as $d) {
                        CuadGrupoCuadrilleroFecha::updateOrCreate(
                            [
                                'cuadrillero_id' => $cuadrilleroId,
                                'fecha' => $d->toDateString(),
                            ],
                            [
                                'codigo_grupo' => $codigoGrupo,
                            ]
                        );
                    }
                }
                /*
                                // ✅ Verificar si no tiene ningún registro en la semana
                                $existenRegistros = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                                    ->whereBetween('fecha', [$inicioDate, $finDate])
                                    ->exists();

                                if (!$existenRegistros) {
                                    foreach ($dias as $d) {
                                        CuadRegistroDiario::create([
                                            'cuadrillero_id' => $cuadrilleroId,
                                            'fecha' => $d->toDateString(),
                                            'asistencia' => false,
                                            'total_horas' => 0,
                                            'costo_dia' => 0,
                                            'total_bono' => 0,
                                            'costo_personalizado_dia' => null,
                                        ]);
                                    }
                                }*/

                // ✅ Guardar asistencias diarias
                foreach ($dias as $index => $d) {
                    $fechaStr = $d->toDateString();
                    $valorBruto = $fila["dia_" . ($index + 1)] ?? 0;
                    $total_horas = floatval($valorBruto);

                    CuadRegistroDiario::updateOrCreate(
                        [
                            'cuadrillero_id' => $cuadrilleroId,
                            'fecha' => $fechaStr,
                        ],
                        [
                            'asistencia' => true,
                            'total_horas' => $total_horas,
                            'costo_dia' => 0,
                            'total_bono' => 0
                        ]
                    );
                }
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public static function obtenerHandsontableReporteDiario($fecha,$tramoSeleccionadoId)
    {
        $fecha = Carbon::parse($fecha)->format('Y-m-d');

        // 1️⃣ Obtener todos los registros diarios para la fecha
        $registros = CuadRegistroDiario::with([
            'cuadrillero:id,nombres,dni',
            'detalleHoras.actividad',
        ])
            ->where('fecha', $fecha)
            ->where('total_horas', '>', 0)
            ->orderBy(
                Cuadrillero::select('nombres')
                    ->whereColumn('cuadrilleros.id', 'cuad_registros_diarios.cuadrillero_id')
                    ->limit(1)
            )
            ->orderBy('codigo_grupo')
            ->get();


        if ($registros->isEmpty()) {
            return [
                'data' => [],
                'total_columnas' => 0
            ];
        }

        // 2️⃣ Preparar datos y conteo
        $resultados = [];
        $maxActividades = 0;

        foreach ($registros as $registro) {
            
            $cuadrillero = $registro->cuadrillero;

            $todasActividades = collect();
            foreach ($registro->detalleHoras as $detalle) {
                $todasActividades->push([
                    'campo' => $detalle->campo_nombre,
                    'labor' => $detalle->codigo_labor,
                    'hora_inicio' => Carbon::parse($detalle->hora_inicio)->format('H:i'),
                    'hora_fin' => Carbon::parse($detalle->hora_fin)->format('H:i'),
                ]);
            }

            $todasActividades = $todasActividades->sortBy('hora_inicio')->values();
            $maxActividades = max($maxActividades, $todasActividades->count());

            $fila = [
                'cuadrillero_id' => $cuadrillero->id,
                'codigo_grupo' => $registro->codigo_grupo, 
                'cuadrillero_nombres' => $cuadrillero->nombres,
                'cuadrillero_dni' => $cuadrillero->dni
            ];

            $totalHoras = 0;

            foreach ($todasActividades as $index => $actividad) {
                $n = $index + 1;
                $fila["campo_$n"] = $actividad['campo'];
                $fila["labor_$n"] = $actividad['labor'];
                $fila["hora_inicio_$n"] = $actividad['hora_inicio'];
                $fila["hora_fin_$n"] = $actividad['hora_fin'];

                // Sumar diferencia en horas
                $inicio = Carbon::createFromFormat('H:i', $actividad['hora_inicio']);
                $fin = Carbon::createFromFormat('H:i', $actividad['hora_fin']);
                $horas = $inicio->diffInMinutes($fin) / 60;
                $totalHoras += $horas;
            }

            // Redondear a 2 decimales por si acaso
            $fila["total_horas"] = round($totalHoras, 2);
            $fila["total_horas_validado"] = $registro->total_horas_validado;

            $resultados[] = $fila;
        }

        return [
            'data' => $resultados,
            'total_columnas' => $maxActividades
        ];
    }

    public static function registrarActividadDiaria($fecha, array $cuadrilleros, array $actividades)
    {
        if (!$fecha) {
            throw ValidationException::withMessages([
                'fecha' => 'Debe seleccionar una fecha.'
            ]);
        }
        // Validar mínimo un cuadrillero
        if (count($cuadrilleros) === 0) {
            throw ValidationException::withMessages([
                'cuadrilleros' => 'Debe agregar al menos un cuadrillero.'
            ]);
        }

        // Validar mínimo una actividad
        if (count($actividades) === 0) {
            throw ValidationException::withMessages([
                'actividades' => 'Debe registrar al menos una actividad.'
            ]);
        }

        // Validar estructura de actividades
        $validator = Validator::make(
            ['actividades' => $actividades],
            [
                'actividades.*.inicio' => 'required',
                'actividades.*.fin' => 'required',
                'actividades.*.campo' => 'required',
                'actividades.*.labor' => 'required',
            ],
            [
                'actividades.*.inicio.required' => 'Cada actividad debe tener una hora de inicio.',
                'actividades.*.fin.required' => 'Cada actividad debe tener una hora de fin.',
                'actividades.*.campo.required' => 'Cada actividad debe tener un campo.',
                'actividades.*.labor.required' => 'Cada actividad debe tener una labor.',
            ]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        foreach ($cuadrilleros as $cuadrillero) {
            // 1️⃣ Buscar o crear el registro diario para ese cuadrillero y fecha
            $registroDiario = CuadRegistroDiario::firstOrCreate(
                [
                    'cuadrillero_id' => $cuadrillero['id'],
                    'fecha' => $fecha,
                ],
                [
                    'asistencia' => true,
                    'costo_dia' => 0,
                    'total_bono' => 0,
                    'costo_personalizado_dia' => null,
                ]
            );

            foreach ($actividades as $actividad) {


                // 3️⃣ Evitar duplicados de detalle exacto
                $yaExisteDetalle = CuadDetalleHora::where('registro_diario_id', $registroDiario->id)
                    ->where('codigo_labor', $actividad['labor'])
                    ->where('hora_inicio', $actividad['inicio'])
                    ->where('hora_fin', $actividad['fin'])
                    ->exists();

                if (!$yaExisteDetalle) {
                    // 4️⃣ Crear detalle de horas
                    CuadDetalleHora::create([
                        'registro_diario_id' => $registroDiario->id,
                        'codigo_labor' => $actividad['labor'],
                        'campo_nombre' => $actividad['campo'],
                        'hora_inicio' => $actividad['inicio'],
                        'hora_fin' => $actividad['fin'],
                        'produccion' => null,
                        'costo_bono' => 0,
                    ]);
                }
            }
        }


        return true;
    }

    /**
     * Guarda tramos de cuadrilleros desde un array de filas tipo Handsontable.
     * 
     * - No se crean registros diarios nuevos, solo se actualizan existentes.
     * - Si no hay tramos en una fila, se eliminan todos los detalles actuales del registro.
     * - Si hay tramos, se hace una comparación diferencial (no borra todo) y mantiene los existentes.
     * - Cada registro tendrá un campo "horas_validadas" que indica si el total de horas detalladas coincide con el total_horas del registro diario.
     * 
     * @param string $fecha Fecha del registro (YYYY-MM-DD)
     * @param array $rows Arreglo de filas con datos de tramos
     * @return bool
     * @throws Exception|ValidationException
     */
    public static function guardarDesdeHandsontable($fecha, $rows)
    {
        DB::beginTransaction();
        try {
            if (!$fecha) {
                throw ValidationException::withMessages([
                    'fecha' => 'Debe especificar una fecha.'
                ]);
            }

            $labores = Labores::all()->pluck('id', 'codigo')->toArray();
            $errores = [];
            $maxCol = 0;
            if (!empty($rows)) {
                foreach (array_keys($rows[0]) as $key) {
                    if (preg_match('/^campo_(\d+)$/', $key, $matches)) {
                        $maxCol = max($maxCol, (int) $matches[1]);
                    }
                }
            }

            foreach ($rows as $i => $fila) {
                
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                $codigoGrupo = $fila['codigo_grupo'] ?? null;
                $filaOrden = $i + 1;

                $tramos = [];
                for ($j = 1; $j <= $maxCol; $j++) {
                    $inicio = $fila["hora_inicio_$j"] ?? null;
                    $fin = $fila["hora_fin_$j"] ?? null;
                    $campo = $fila["campo_$j"] ?? null;
                    $labor = $fila["labor_$j"] ?? null;

                    if ($labor && !array_key_exists($labor, $labores)) {
                        throw new Exception("Error en la fila {$filaOrden}, el código {$labor} no existe.");
                    }

                    if ($inicio || $fin || $campo || $labor) {
                        if (!$inicio || !$fin || !$labor) {
                            $errores[] = "Fila " . ($i + 1) . ", tramo $j: falta hora o labor.";
                            continue;
                        }

                        $tramos[] = [
                            'codigo_labor' => $labor,
                            'campo_nombre' => $campo,
                            'hora_inicio' => $inicio,
                            'hora_fin' => $fin,
                        ];
                    }
                }

                if (empty($tramos)) {
                    $registro = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                        ->where('fecha', $fecha)
                        ->where('codigo_grupo',$codigoGrupo)
                        ->first();
                    if (!$registro) {
                        throw new Exception("No existe el registro con fecha {$fecha} e id {$cuadrilleroId}");
                    }
                    $existentes = $registro->detalleHoras()->delete();
                    continue;
                }

                // Registro diario
                $registro = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                    ->where('fecha', $fecha)
                    ->where('codigo_grupo',$codigoGrupo)
                    ->first();
                if (!$registro) {
                    throw new Exception("No existe el registro con fecha {$fecha} e id {$cuadrilleroId}");
                }
                // Obtener tramos existentes
                $existentes = $registro->detalleHoras()->get();


                // Mapear claves para comparar
                $clave = fn($tramo) => implode('|', [
                    $tramo['codigo_labor'],
                    $tramo['campo_nombre'],
                    Carbon::parse($tramo['hora_inicio'])->format('H:i'),
                    Carbon::parse($tramo['hora_fin'])->format('H:i'),
                ]);
                $existentesMap = $existentes->keyBy($clave);
                $nuevosMap = collect($tramos)->keyBy($clave);

                // Eliminar los que ya no existen
                foreach ($existentes as $existente) {

                    $k = $clave($existente->toArray());

                    if (!$nuevosMap->has($k)) {
                        // dd($existentes,$tramos,$nuevosMap,$k,$existente);
                        $existente->delete();
                    }
                }
                //dd($nuevosMap);
                // Insertar o actualizar los actuales
                foreach ($nuevosMap as $k => $nuevo) {
                    $detalle = $existentesMap->get($k);

                    if ($detalle) {
                        // Ya existe, se mantiene. Si necesitas actualizar algún campo adicional, hazlo aquí.
                        continue;
                    }

                    // Crear nuevo
                    $registro->detalleHoras()->create([
                        'codigo_labor' => $nuevo['codigo_labor'],
                        'campo_nombre' => $nuevo['campo_nombre'],
                        'hora_inicio' => $nuevo['hora_inicio'],
                        'hora_fin' => $nuevo['hora_fin'],
                        'produccion' => null,
                        'costo_bono' => 0,
                    ]);
                }
            }

            if (count($errores)) {
                throw ValidationException::withMessages(['errores' => $errores]);
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected static function getLaborNombre($laborId)
    {
        return optional(Labores::find($laborId))->nombre_labor ?? 'Labor desconocida';
    }

    protected static function getLaborCodigo($laborId)
    {
        return optional(Labores::find($laborId))->codigo ?? 'N/A';
    }

    public static function getCuadrillerosCompleto()
    {
        return Cuadrillero::get()->map(fn($c) => [
            "id" => $c->id,
            "dni" => $c->dni,
            "name" => $c->nombres
        ]);
    }

    public static function buscarSemana(string $fecha): CuaAsistenciaSemanal
    {
        $semana = CuaAsistenciaSemanal::whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->firstOrFail();

        if (!$semana) {
            throw new \Exception("No hay una semana para esta fecha {$fecha}");
        }

        return $semana;
    }
    /**
     * Devuelve los cuadrilleros con asistencia en una fecha dada
     */
    public static function obtenerCuadrillerosEnFecha(string $fecha)
    {
        $semana = self::buscarSemana($fecha);
        $grupos = $semana->grupos;

        if (!$grupos) {
            throw new \Exception("No hay ningún grupo en el registro semanal {$semana->id}");
        }

        $lista = [];

        foreach ($grupos as $grupo) {
            $cuadrilleros = $grupo->cuadrillerosEnAsistencia;
            if ($cuadrilleros) {
                foreach ($cuadrilleros as $cuadrillero) {
                    $data = $cuadrillero->cuadrillero;
                    $lista[] = [
                        'cua_asi_sem_cua_id' => $cuadrillero->id,
                        'id' => $data->id,
                        'grupo' => $grupo->id,
                        'grupo_nombre' => $grupo->grupo->nombre,
                        'tipo' => 'cuadrilla',
                        'dni' => $data->dni,
                        'nombres' => $data->nombres,
                    ];
                }
            }
        }

        return collect($lista)->sortBy(['grupo', 'nombres'])->values();
    }
   

}
