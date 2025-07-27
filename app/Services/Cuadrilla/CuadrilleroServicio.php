<?php

namespace App\Services\Cuadrilla;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadDetalleHora;
use App\Models\CuadGrupoCuadrilleroFecha;
use App\Models\CuadOrdenSemanal;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\CuadrilleroActividad;
use App\Models\CuaGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Models\Labores;
use App\Support\FormatoHelper;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Str;

class CuadrilleroServicio
{
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
        // Obtener todos los grupos por cuadrillero y fecha en lote
        $gruposPorDia = CuadGrupoCuadrilleroFecha::whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->keyBy(fn($g) => $g->cuadrillero_id . '|' . $g->fecha);

        // Cargar todos los registros diarios
        $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
            ->with('cuadrillero')
            ->get()
            ->filter(function ($registro) use ($grupo, $nombre) {
                return (!$nombre || stripos($registro->cuadrillero->nombres ?? '', $nombre) !== false);
            });

        // Agrupar por cuadrillero_id + grupo
        $registrosAgrupados = [];

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
                    'total' => 0, // inicializar total
                ];
            }

            $fechaIndex = $fechas->search($fechaKey) + 1;
            $registrosAgrupados[$grupoKey]["jornal_{$fechaIndex}"] = $registro->costo_dia;
            $registrosAgrupados[$grupoKey]["pagado_{$fechaIndex}"] = $registro->esta_pagado ? 1 : 0;

            // Sumar al total
            $registrosAgrupados[$grupoKey]['total'] += floatval($registro->costo_dia);
        }


        return array_values($registrosAgrupados);
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
                    'fecha' => Carbon::parse($gasto->fecha_gasto)->format('d/m/Y'),
                    'monto' => $gasto->monto
                ];
            })->toArray();
    }

    public static function guardarGastosAdicionalesXGrupo($datos, $inicio, $rangoDias)
    {
        $inicioCarbon = Carbon::parse($inicio)->startOfDay();
        $finCarbon = (clone $inicioCarbon)->addDays($rangoDias)->endOfDay();

        // 1. Eliminar los existentes en el rango
        GastoAdicionalPorGrupoCuadrilla::whereBetween('fecha_gasto', [$inicioCarbon, $finCarbon])->delete();

        // 2. Insertar todos los nuevos
        foreach ($datos as $fila) {
            // Validar que exista el grupo por nombre
            $grupo = CuaGrupo::where('nombre', $fila['grupo'])->first();

            if (!$grupo) {
                throw new Exception("No se encontró el grupo con nombre '{$fila['grupo']}'");
            }

            // Convertir fecha
            $fecha = FormatoHelper::parseFecha($fila['fecha']);

            GastoAdicionalPorGrupoCuadrilla::create([
                'monto' => $fila['monto'],
                'descripcion' => $fila['descripcion'],
                'anio_contable' => Carbon::parse($fecha)->year,
                'mes_contable' => Carbon::parse($fecha)->month,
                'fecha_gasto' => $fecha,
                'codigo_grupo' => $grupo->codigo, // Usa la nueva columna
            ]);
        }
    }

    public static function guardarBonoCuadrilla($fila, $fecha)
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
    /*
    public static function guardarBonificacionesYConfiguracionActividad($actividadId, $datos, $tramos, $unidades, $estandarProduccion)
    {
        $actividad = Actividad::findOrFail($actividadId);
        $fecha = $actividad->fecha;

        // 1️⃣ Guardar la configuración en la actividad
        $actividad->update([
            'tramos_bonificacion' => json_encode($tramos),
            'unidades' => $unidades,
            'estandar_produccion' => $estandarProduccion
        ]);

        // 2️⃣ Para cada fila de datos
        foreach ($datos as $fila) {

            $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
            $campo = $fila['campo'] ?? null;
            $labor = $fila['labor'] ?? null;
            $totalBono = floatval($fila['total_bono'] ?? 0);

            if (!$cuadrilleroId) {
                continue; // Nada que hacer si no hay cuadrillero o bono
            }

            // Buscar el registro diario para la fecha
            $registro = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                ->whereDate('fecha', $fecha)
                ->first();

            if (!$registro) {
                continue; // No existe registro diario
            }

            // Obtener detalles de esa actividad, ordenados por horario
            $detalles = $registro->detalleHoras()
                ->where('campo_nombre', $campo)
                ->where('codigo_labor', $labor)
                ->orderBy('hora_inicio')
                ->get();

            $conteoTramos = $detalles->count();

            if ($conteoTramos === 0) {
                continue;
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
    }*/



    public static function obtenerHandsontableRegistrosPorActividad($actividadId)
    {

        $actividad = Actividad::find($actividadId);
        if (!$actividad) {
            throw new Exception('No existe la actividad');
        }

        $campo_nombre = $actividad->campo;
        $codigo_labor = $actividad->codigo_labor;
        $registros = CuadRegistroDiario::with([
            'cuadrillero',
            'detalleHoras'
        ])->where('fecha', $actividad->fecha)
            ->whereHas('detalleHoras', function ($query) use ($campo_nombre, $codigo_labor) {

                $query->where('campo_nombre', $campo_nombre);
                $query->where('codigo_labor', $codigo_labor);
            })->get();

        // 🟦 Generar colores por horario único
        $horariosUnicos = collect();
        foreach ($registros as $r) {
            foreach ($r->detalleHoras as $d) {
                $inicio = \Carbon\Carbon::parse($d->hora_inicio)->format('H:i');
                $fin = \Carbon\Carbon::parse($d->hora_fin)->format('H:i');
                $horariosUnicos->push("$inicio-$fin");
            }
        }

        $horariosUnicos = $horariosUnicos->unique()->values()->slice(0, 10);

        // 🟩 Preparar filas para Handsontable
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

            $detalles = $r->detalleHoras;
            $bono = 0;
            $horariosConcatenados = [];

            foreach ($detalles as $i => $d) {
                $inicio = \Carbon\Carbon::parse($d->hora_inicio)->format('H:i');
                $fin = \Carbon\Carbon::parse($d->hora_fin)->format('H:i');
                $key = $inicio . '-' . $fin;
                $row["produccion_" . ($i + 1)] = $d->produccion ?? 0;
                $bono += $d->costo_bono ?? 0;
                $horariosConcatenados[] = $key;
            }

            $maxTramos = max($maxTramos, $detalles->count());
            $row['horarios'] = implode(', ', $horariosConcatenados);
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

        // 🟠 1. Registros de asistencia
        $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
            ->with(['cuadrillero:id,nombres,dni,codigo_grupo'])
            ->get()
            ->groupBy('cuadrillero_id');

        // 🟠 2. Relación de grupo personalizado por día
        $grupoPorFecha = CuadGrupoCuadrilleroFecha::whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->groupBy(fn($r) => $r->cuadrillero_id . '|' . $r->fecha);

        $registrosPorOrden = CuadOrdenSemanal::whereDate('fecha_inicio', $inicio)
            ->with(['cuadrillero'])
            ->orderBy('codigo_grupo')
            ->orderBy('orden')
            ->get()
            ->keyBy('cuadrillero_id');
        $resultados = [];

        foreach ($registrosPorOrden as $cuadrilleroId => $registro) {

            $cuadrillero = $registro->cuadrillero;
            $registrosDiarios = $registros[$cuadrillero->id] ?? null;
            //dd($registrosDiarios);
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

                $bono = optional($registroHoras)->total_bono ?? 0;
                $costo_dia = optional($registroHoras)->costo_dia;
                $costo_dia = ($costo_dia && $costo_dia > 0) ? $costo_dia : '-';

                // Agregar columnas planas
                $fila["dia_" . ($index + 1)] = $total_horas;
                $fila["jornal_" . ($index + 1)] = $costo_dia;
                $fila["bono_" . ($index + 1)] = $bono;

                // Totales
                $totalCostos += (float) $costo_dia;
                $totalBonos += $bono;
            }

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

        return [
            'data' => $resultados,
            'headers' => $headers,
            'total_dias' => $totalDias,
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
                $valor = $grupo["dia_$i"];
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

    public static function obtenerHandsontableCostosAsignados($fechaInicio, $fechaFin)
    {
        // ✅ 1. Calcular fechas por defecto si vienen null
        if (!$fechaInicio || !$fechaFin) {
            $hoy = Carbon::today();

            // Inicio: Lunes de la semana actual
            $inicioSemana = $hoy->copy()->startOfWeek(weekStartsAt: Carbon::MONDAY);

            // Fin: 7 días después (lunes siguiente)
            $finSemana = $inicioSemana->copy()->addDays(6);

            // Si quieres exactamente lunes -> lunes+7, usa:
            // $finSemana = $inicioSemana->copy()->addDays(7);

            $fechaInicio = $inicioSemana->toDateString();
            $fechaFin = $finSemana->toDateString();
        }

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
        $gruposUsadosEnCostos = $costosGuardados->keys();

        // Incluye también grupos activos aunque no tengan costos aún
        $gruposActivos = CuaGrupo::where('estado', true)->pluck('codigo');

        $gruposFinales = $gruposUsadosEnCostos->merge($gruposActivos)->unique();

        $result = collect();
        $total_dias = 0;

        foreach ($gruposFinales as $codigo) {
            $grupo = CuaGrupo::where('codigo', $codigo)->first();

            if (!$grupo)
                continue;

            $total_dias = $dias->count();

            $item = [
                'codigo_grupo' => $codigo,
                'nombre' => $grupo->nombre,
                'color' => $grupo->color,
                'modalidad_pago' => $grupo->modalidad_pago,
                'costo_dia_sugerido' => $grupo->costo_dia_sugerido,
                'estado' => $grupo->estado
            ];

            $index = 1;
            foreach ($dias as $dia) {
                $valor = optional(
                    $costosGuardados->get($codigo)?->firstWhere('fecha', $dia)
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

        $registroDiarioCuadrilla = CuadRegistroDiario::whereBetween('fecha', [$inicioDate, $finDate])->get();
        $costosDiariosDuranteFechas = CuadCostoDiarioGrupo::whereBetween('fecha', [$inicioDate, $finDate])->get();
        $grupoCuadrilleroEnFechas = CuadGrupoCuadrilleroFecha::whereBetween('fecha', [$inicioDate, $finDate])->get();

        foreach ($registroDiarioCuadrilla as $asistenciaCuadrillero) {
            $totalHoras = (float) $asistenciaCuadrillero->total_horas;


            if ($totalHoras <= 0) {
                $asistenciaCuadrillero->costo_dia = 0;
                // Actualizar también total_bono en cero
                $asistenciaCuadrillero->total_bono = 0;
                $asistenciaCuadrillero->save();
                continue;
            }

            $fechaStr = $asistenciaCuadrillero->fecha instanceof \Carbon\Carbon
                ? $asistenciaCuadrillero->fecha->toDateString()
                : (string) $asistenciaCuadrillero->fecha;

            $grupoCuadrilleroEnFecha = $grupoCuadrilleroEnFechas
                ->where('fecha', $fechaStr)
                ->where('cuadrillero_id', $asistenciaCuadrillero->cuadrillero_id)
                ->first();

            if (!$grupoCuadrilleroEnFecha) {
                $asistenciaCuadrillero->costo_dia = 0;
                $asistenciaCuadrillero->total_bono = 0;
                $asistenciaCuadrillero->save();
                continue;
            }

            $costoDiario = $costosDiariosDuranteFechas
                ->where('fecha', $fechaStr)
                ->where('codigo_grupo', $grupoCuadrilleroEnFecha->codigo_grupo)
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
                $asistenciaCuadrillero->total_bono = 0;
                $asistenciaCuadrillero->save();
                continue;
            }

            $costo_dia = ($totalHoras / 8) * $costoEseDiaX8Horas;
            $asistenciaCuadrillero->costo_dia = round($costo_dia, 2);

            // ➜ Calcular el total_bono sumando los costo_bono de sus detalles
            $totalBono = $asistenciaCuadrillero->detalleHoras()->sum('costo_bono');
            $asistenciaCuadrillero->total_bono = round($totalBono, 2);

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

        // Rango de 7 días desde la fecha de inicio
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

        CuadRegistroDiario::whereIn('fecha', $fechas)
            ->whereNotIn('cuadrillero_id', $cuadrilleroIds)
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

    /*
    public static function registrarOrdenSemanal($fechaInicio, $rows)
    {
        $grupoArrays = [];

        foreach ($rows as &$row) {

            // ✅ 1. Filtrar vacíos (eliminados por usuario)
            if (empty(trim($row['cuadrillero_nombres'] ?? ''))) {
                continue;  // no lo procesamos, se borró arriba
            }

            $grupo = $row['codigo_grupo'] ?? 'SIN GRUPO';
            $cuadrilleroId = $row['cuadrillero_id'] ?? null;

            // ✅ 2. Normalizar ID y Nombre
            if ($cuadrilleroId) {
                // Verificar si nombre coincide
                $cuadrillero = Cuadrillero::find($cuadrilleroId);
                if (!$cuadrillero || mb_strtoupper($cuadrillero->nombres) !== mb_strtoupper($row['cuadrillero_nombres'])) {
                    // Buscar por nombre
                    $nuevo = Cuadrillero::whereRaw('UPPER(nombres) = ?', [mb_strtoupper($row['cuadrillero_nombres'])])->first();
                    if ($nuevo) {
                        $row['cuadrillero_id'] = $nuevo->id;
                    } else {
                        // Crear nuevo
                        $nuevo = Cuadrillero::create([
                            'nombres' => $row['cuadrillero_nombres'],
                            'codigo_grupo' => $row['codigo_grupo'] == 'SIN GRUPO' ? null : $row['codigo_grupo'],
                        ]);
                        $row['cuadrillero_id'] = $nuevo->id;
                    }
                }
            } else {
                // No hay id → buscar por nombre
                $nuevo = Cuadrillero::whereRaw('UPPER(nombres) = ?', [mb_strtoupper($row['cuadrillero_nombres'])])->first();
                if ($nuevo) {
                    $row['cuadrillero_id'] = $nuevo->id;
                } else {
                    // Crear nuevo
                    $nuevo = Cuadrillero::create([
                        'nombres' => $row['cuadrillero_nombres'],
                        'codigo_grupo' => $row['codigo_grupo'] == 'SIN GRUPO' ? null : $row['codigo_grupo'],
                    ]);
                    $row['cuadrillero_id'] = $nuevo->id;
                }
            }

            // ✅ 3. Clasificar por grupo
            $grupoArrays[$grupo][] = $row;
        }

        // ✅ 4. Reconstruir lista final ordenada
        $listaFinal = [];
        foreach ($grupoArrays as $grupo => $lista) {
            if ($grupo === 'SIN GRUPO' || !$grupo || trim($grupo) === '')
                continue;
            foreach ($lista as $item) {
                $listaFinal[] = $item;
            }
        }

        if (isset($grupoArrays['SIN GRUPO'])) {
            foreach ($grupoArrays['SIN GRUPO'] as $item) {
                $listaFinal[] = $item;
            }
        }

        // ✅ 5. Asignar orden e insertar
        $orden = 1;
        foreach ($listaFinal as &$item) {
            $item['orden'] = $orden++;

            // ✅ Verificar duplicidad antes de crear
            $yaExiste = CuadOrdenSemanal::where('cuadrillero_id', $item['cuadrillero_id'])
                ->where('fecha_inicio', $fechaInicio)
                ->exists();

            if ($yaExiste) {
                $nombre = $item['cuadrillero_nombres'] ?? 'ID: ' . $item['cuadrillero_id'];
                throw new Exception("El cuadrillero '{$nombre}' ya está registrado en la semana que inicia el {$fechaInicio}.");
            }

            CuadOrdenSemanal::create([
                'cuadrillero_id' => $item['cuadrillero_id'],
                'fecha_inicio' => $fechaInicio,
                'orden' => $item['orden'],
            ]);
        }

        return $listaFinal;
    }*/


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


    public static function obtenerHandsontableReporteDiario($fecha)
    {
        $fecha = Carbon::parse($fecha)->format('Y-m-d');

        // 1️⃣ Obtener todos los registros diarios para la fecha
        $registros = CuadRegistroDiario::with([
            'cuadrillero:id,nombres,dni',
            'detalleHoras.actividad'
        ])
            ->where('fecha', $fecha)
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

        foreach ($registros->groupBy('cuadrillero_id') as $cuadrilleroId => $registrosCuadrillero) {

            $cuadrillero = optional($registrosCuadrillero->first()->cuadrillero);
            $asistencia = $registrosCuadrillero->first()->asistencia;

            // Recolectar TODOS los detalles de este cuadrillero
            $todasActividades = collect();
            foreach ($registrosCuadrillero as $registro) {
                foreach ($registro->detalleHoras as $detalle) {

                    $todasActividades->push([
                        'campo' => $detalle->campo_nombre,
                        'labor' => $detalle->codigo_labor,
                        'hora_inicio' => Carbon::parse($detalle->hora_inicio)->format('H:i'),
                        'hora_fin' => Carbon::parse($detalle->hora_fin)->format('H:i'),
                    ]);
                }
            }

            // Ordenar actividades por hora_inicio ASC
            $todasActividades = $todasActividades->sortBy('hora_inicio')->values();

            // Actualizar máximo de columnas
            $maxActividades = max($maxActividades, $todasActividades->count());

            // Construir fila final
            $fila = [
                'cuadrillero_id' => $cuadrilleroId,
                'cuadrillero_nombres' => $cuadrillero->nombres,
                'cuadrillero_dni' => $cuadrillero->dni,
                'asistencia' => $asistencia,
            ];

            foreach ($todasActividades as $index => $actividad) {
                $n = $index + 1;
                $fila["campo_$n"] = $actividad['campo'];
                $fila["labor_$n"] = $actividad['labor'];
                $fila["hora_inicio_$n"] = $actividad['hora_inicio'];
                $fila["hora_fin_$n"] = $actividad['hora_fin'];
            }

            $resultados[] = $fila;
        }

        // 3️⃣ Ordenar lista por popularidad de actividades
        $resultados = collect($resultados)
            ->sortByDesc(fn($item) => self::contarActividadesEnFila($item))
            ->values()
            ->all();

        return [
            'data' => $resultados,
            'total_columnas' => $maxActividades
        ];
    }

    protected static function contarActividadesEnFila($fila)
    {
        $count = 0;
        foreach ($fila as $key => $value) {
            if (Str::startsWith($key, 'campo_') && !empty($value)) {
                $count++;
            }
        }
        return $count;
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
    /*
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
            $usuarioId = Auth::id();
            $errores = [];

            foreach ($rows as $i => $fila) {

                $cuadrilleroNombre = trim($fila['cuadrillero_nombres'] ?? '');
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                $tieneTramos = false;
                $tramos = [];
                $filaOrden = $i + 1;

                // 2️⃣ Recolectar tramos válidos
                // como maximo habran 10 labores por trabajador, mas adelante debemos detectar cuantos son
                for ($j = 1; $j <= 10; $j++) {
                    $inicio = $fila["hora_inicio_$j"] ?? null;
                    $fin = $fila["hora_fin_$j"] ?? null;
                    $campo = $fila["campo_$j"] ?? null;
                    $labor = $fila["labor_$j"] ?? null;

                    if ($labor) {
                        if (!array_key_exists($labor, $labores)) {

                            throw new Exception("Error en la fila {$filaOrden}, el código {$labor} no existe.");
                        }
                    }

                    if ($inicio || $fin || $campo || $labor) {
                        if (!$inicio || !$fin || !$labor) {
                            $errores[] = "Fila " . ($i + 1) . ", tramo $j: falta hora o labor.";
                            continue;
                        }

                        $tieneTramos = true;
                        $tramos[] = compact('inicio', 'fin', 'campo', 'labor');
                    }
                }

                $registro = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                    ->where('fecha', $fecha)
                    ->first();

                if ($registro) {
                    $registro->detalleHoras()->delete();
                }

                // 5️⃣ Crear o actualizar registro diario
                $registro = CuadRegistroDiario::updateOrCreate(
                    [
                        'cuadrillero_id' => $cuadrilleroId,
                        'fecha' => $fecha,
                    ],
                    [
                        'asistencia' => $fila['asistencia'] ?? true,
                        'costo_dia' => 0,
                        'total_bono' => 0,
                        'costo_personalizado_dia' => null,
                    ]
                );

                foreach ($tramos as $tramo) {

                    $detalle = CuadDetalleHora::firstOrCreate(
                        [
                            'registro_diario_id' => $registro->id,
                            'codigo_labor' => $tramo['labor'],
                            'campo_nombre' => $tramo['campo'],
                            'hora_inicio' => $tramo['inicio'],
                            'hora_fin' => $tramo['fin'],
                        ],
                        [
                            'produccion' => null,
                            'costo_bono' => 0,
                        ]
                    );
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
    }*/
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
            $usuarioId = Auth::id();
            $errores = [];

            foreach ($rows as $i => $fila) {

                $cuadrilleroNombre = trim($fila['cuadrillero_nombres'] ?? '');
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                $filaOrden = $i + 1;

                $tramos = [];
                for ($j = 1; $j <= 10; $j++) {
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
                    continue;
                }

                // Registro diario
                $registro = CuadRegistroDiario::updateOrCreate(
                    [
                        'cuadrillero_id' => $cuadrilleroId,
                        'fecha' => $fecha,
                    ],
                    [
                        'asistencia' => $fila['asistencia'] ?? true,
                        'costo_dia' => 0,
                        'total_bono' => 0,
                        'costo_personalizado_dia' => null,
                    ]
                );

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
                        $existente->delete();
                    }
                }

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
    public static function obtenerTrabajadoresXDia($fecha, $actividadId = null)
    {
        $cuadrillerosEnFecha = self::obtenerCuadrillerosEnFecha($fecha);
        $cuadrillerosAgregados = $cuadrillerosEnFecha->toArray();

        foreach ($cuadrillerosAgregados as $indice => $cuadrilleroAgregado) {
            $cua_asi_sem_cua_id = $cuadrilleroAgregado['cua_asi_sem_cua_id'];

            // valores por defecto
            $cuadrillerosAgregados[$indice]['bono'] = '-';
            $cuadrillerosAgregados[$indice]['horas'] = 0;
            $cuadrillerosAgregados[$indice]['costo_diario'] = 0;
            $cuadrillerosAgregados[$indice]['total'] = 0;

            // Solo si actividadId está presente, buscamos los datos
            if ($actividadId) {
                $actividad = CuadrilleroActividad::where('actividad_id', $actividadId)
                    ->where('cua_asi_sem_cua_id', $cua_asi_sem_cua_id)
                    ->first();

                if ($actividad) {
                    // asignar datos básicos
                    $cuadrillerosAgregados[$indice]['bono'] = $actividad->total_bono ?? 0;
                    $cuadrillerosAgregados[$indice]['horas'] = $actividad->total_horas ?? 0;
                    $cuadrillerosAgregados[$indice]['costo_diario'] = $actividad->total_costo ?? 0;

                    // calcular total
                    $cuadrillerosAgregados[$indice]['total'] =
                        ($actividad->total_costo ?? 0) + ($actividad->total_bono ?? 0);

                    // ahora expandir cantidades
                    $cantidades = $actividad->cantidades ?? [];
                    if (is_string($cantidades)) {
                        $cantidades = json_decode($cantidades, true) ?? [];
                    }

                    foreach ($cantidades as $i => $cantidad) {
                        $key = 'cantidad_' . ($i + 1);
                        $cuadrillerosAgregados[$indice][$key] = $cantidad;
                    }
                }
            }
        }

        return $cuadrillerosAgregados;
    }

}
