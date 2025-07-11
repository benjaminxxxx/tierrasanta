<?php

namespace App\Services\Cuadrilla;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadDetalleHora;
use App\Models\CuadGrupoCuadrilleroFecha;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\CuadrilleroActividad;
use App\Models\CuaGrupo;
use App\Models\Labores;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Str;

class CuadrilleroServicio
{
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
            $totalBono = floatval($fila['total_bono'] ?? 0);

            if (!$cuadrilleroId || $totalBono <= 0) {
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
                ->where('actividad_id', $actividadId)
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
    }



    public static function obtenerHandsontableRegistrosPorActividad($actividadId)
    {
        $coloresDisponibles = [
            '#FF6384',
            '#36A2EB',
            '#FFCE56',
            '#4BC0C0',
            '#9966FF',
            '#FF9F40',
            '#E7E9ED',
            '#8BC34A',
            '#CDDC39',
            '#00BCD4'
        ];

        $registros = CuadRegistroDiario::with([
            'cuadrillero',
            'detalleHoras' => fn($q) => $q->where('actividad_id', $actividadId)
        ])
            ->whereHas('detalleHoras', fn($q) => $q->where('actividad_id', $actividadId))
            ->get();

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
        $mapaColores = [];
        foreach ($horariosUnicos as $i => $h) {
            $mapaColores[$h] = $coloresDisponibles[$i % count($coloresDisponibles)];
        }

        // 🟩 Preparar filas para Handsontable
        $data = [];
        $maxTramos = 0;

        foreach ($registros as $r) {
            $row = [
                'cuadrillero_id' => $r->cuadrillero_id,
                'cuadrillero_nombres' => optional($r->cuadrillero)->nombres ?? '-',
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
            'colores' => $mapaColores,
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

        $resultados = [];
        //dd($registros[15]);

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

        $resultados = collect($resultados)->sortBy(['codigo_grupo', 'nombres'])->values()->toArray();

        return [
            'data' => $resultados,
            'headers' => $headers,
            'total_dias' => $totalDias,
        ];
    }
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
        return CuaGrupo::where('estado', true)->get();
    }
    public static function calcularCostosCuadrilla($inicio, $fin)
    {
        $inicioDate = Carbon::parse($inicio)->startOfDay();
        $finDate = Carbon::parse($fin)->endOfDay();

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


    public static function guardarReporteSemanal($inicio, $fin, $rows)
    {
        DB::beginTransaction();
        try {
            $usuarioId = Auth::id();
            $errores = [];

            $dias = collect();
            $inicioDate = Carbon::parse($inicio)->startOfDay();
            $finDate = Carbon::parse($fin)->endOfDay();
            for ($date = $inicioDate->copy(); $date->lte($finDate); $date->addDay()) {
                $dias->push($date->copy());
            }

            foreach ($rows as $i => $fila) {

                $nombre = trim(mb_strtoupper($fila['cuadrillero_nombres']) ?? '');
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                $codigoGrupo = trim($fila['codigo_grupo'] ?? null);
                $codigoGrupo = $codigoGrupo === 'SIN GRUPO' ? null : $codigoGrupo;

                // 🔴 Caso eliminar
                if ($cuadrilleroId && $nombre === '') {
                    CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                        ->whereBetween('fecha', [$inicio, $fin])
                        ->delete();

                    CuadGrupoCuadrilleroFecha::where('cuadrillero_id', $cuadrilleroId)
                        ->whereBetween('fecha', [$inicio, $fin])
                        ->delete();

                    continue;
                }

                // 🔵 Verificar si nombre e ID coinciden
                if ($cuadrilleroId) {
                    $cuad = Cuadrillero::find($cuadrilleroId);
                    if ($cuad && $cuad->nombres !== $nombre) {
                        // Se "cambió" de trabajador → buscar o crear por nombre
                        $nuevo = Cuadrillero::firstOrCreate(
                            ['nombres' => $nombre],
                            ['estado' => true]
                        );
                        $cuadrilleroId = $nuevo->id;
                    }
                } elseif ($nombre !== '') {
                    // No tenía ID, solo nombre
                    $nuevo = Cuadrillero::firstOrCreate(
                        ['nombres' => $nombre],
                        ['estado' => true]
                    );
                    $cuadrilleroId = $nuevo->id;
                }

                if (!$cuadrilleroId) {
                    // Nada que hacer
                    continue;
                }

                // 🟠 Asignar grupo por rango
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
                }

                // 🟢 Guardar asistencias diarias
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
                            'total_bono' => 0,
                            'costo_personalizado_dia' => null,
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
    }


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

            // ✅ 1️⃣ Antes de todo: obtener todas las actividades de ese día
            $actividadesEseDia = Actividad::where('fecha', $fecha)->pluck('id')->toArray();
            $actividadesUsadasEnLoop = [];

            foreach ($rows as $i => $fila) {
                $cuadrilleroNombre = trim($fila['cuadrillero_nombres'] ?? '');
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                $tieneTramos = false;
                $tramos = [];
                $filaOrden = $i + 1;

                // 2️⃣ Recolectar tramos válidos
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

                // 3️⃣ Si no hay horarios
                if (!$tieneTramos) {
                    if (!$cuadrilleroId && $cuadrilleroNombre === '') {
                        continue;
                    }

                    if (!$cuadrilleroId && $cuadrilleroNombre !== '') {
                        $cuadrillero = Cuadrillero::firstOrCreate(
                            ['nombres' => $cuadrilleroNombre],
                            [
                                'dni' => $fila['cuadrillero_dni'] ?? null,
                                'estado' => true,
                            ]
                        );
                        $cuadrilleroId = $cuadrillero->id;
                    }

                    if ($cuadrilleroId) {
                        $registro = CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                            ->where('fecha', $fecha)
                            ->first();

                        if ($registro) {
                            // ✅ Eliminar SIEMPRE sus detalles
                            $registro->detalleHoras()->delete();

                            if ($cuadrilleroNombre === '') {
                                $registro->delete();
                            } else {
                                $registro->update([
                                    'asistencia' => true,
                                    'costo_dia' => 0,
                                    'total_bono' => 0,
                                    'costo_personalizado_dia' => null,
                                ]);
                            }
                        } else {
                            if ($cuadrilleroNombre !== '') {
                                CuadRegistroDiario::create([
                                    'cuadrillero_id' => $cuadrilleroId,
                                    'fecha' => $fecha,
                                    'asistencia' => true,
                                    'costo_dia' => 0,
                                    'total_bono' => 0,
                                    'costo_personalizado_dia' => null,
                                ]);
                            }
                        }
                    }

                    continue;
                }

                // 4️⃣ Obtener o crear cuadrillero
                if (!$cuadrilleroId && $cuadrilleroNombre !== '') {
                    $cuadrillero = Cuadrillero::firstOrCreate(
                        ['nombres' => $cuadrilleroNombre],
                        [
                            'dni' => $fila['cuadrillero_dni'] ?? null,
                            'estado' => true,
                        ]
                    );
                    $cuadrilleroId = $cuadrillero->id;
                }

                if (!$cuadrilleroId) {
                    $errores[] = "Fila " . ($i + 1) . ": no se pudo asociar a ningún cuadrillero.";
                    continue;
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

                $idsDetallesNuevos = [];

                foreach ($tramos as $tramo) {
                    $laborId = $labores[$tramo['labor']];
                    $actividad = Actividad::firstOrCreate(
                        [
                            'fecha' => $fecha,
                            'campo' => $tramo['campo'],
                            'labor_id' => $laborId,
                        ],
                        [
                            'nombre_labor' => self::getLaborNombre($laborId),
                            'codigo_labor' => $tramo['labor'],
                            'created_by' => $usuarioId,
                        ]
                    );

                    // ✅ Marcarla como usada
                    $actividadesUsadasEnLoop[] = $actividad->id;

                    $detalle = CuadDetalleHora::firstOrCreate(
                        [
                            'registro_diario_id' => $registro->id,
                            'actividad_id' => $actividad->id,
                            'hora_inicio' => $tramo['inicio'],
                            'hora_fin' => $tramo['fin'],
                        ],
                        [
                            'campo_nombre' => $tramo['campo'],
                            'produccion' => null,
                            'costo_bono' => 0,
                        ]
                    );

                    $idsDetallesNuevos[] = $detalle->id;
                }

                CuadDetalleHora::where('registro_diario_id', $registro->id)
                    ->whereNotIn('id', $idsDetallesNuevos)
                    ->delete();
            }

            // ✅ 6️⃣ Limpiar actividades huérfanas
            $actividadesUsadasEnLoop = array_unique($actividadesUsadasEnLoop);
            $actividadesParaEliminar = array_diff($actividadesEseDia, $actividadesUsadasEnLoop);

            if (!empty($actividadesParaEliminar)) {
                Actividad::whereIn('id', $actividadesParaEliminar)->delete();
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
            /** @var \App\Models\Cuadrillero $cuadrillero */
            $cuadrillero = optional($registrosCuadrillero->first()->cuadrillero);
            $asistencia = $registrosCuadrillero->first()->asistencia;

            // Recolectar TODOS los detalles de este cuadrillero
            $todasActividades = collect();
            foreach ($registrosCuadrillero as $registro) {
                foreach ($registro->detalleHoras as $detalle) {

                    $todasActividades->push([
                        'campo' => $detalle->campo_nombre,
                        'labor' => optional($detalle->actividad)->codigo_labor ?? '-',
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

        $usuarioId = Auth::id();

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
                // 2️⃣ Buscar o crear la actividad
                $actividadModelo = Actividad::firstOrCreate(
                    [
                        'fecha' => $fecha,
                        'campo' => $actividad['campo'],
                        'labor_id' => $actividad['labor'],
                    ],
                    [
                        'nombre_labor' => self::getLaborNombre($actividad['labor']),
                        'codigo_labor' => self::getLaborCodigo($actividad['labor']),
                        'created_by' => $usuarioId,
                    ]
                );

                // 3️⃣ Evitar duplicados de detalle exacto
                $yaExisteDetalle = CuadDetalleHora::where('registro_diario_id', $registroDiario->id)
                    ->where('actividad_id', $actividadModelo->id)
                    ->where('hora_inicio', $actividad['inicio'])
                    ->where('hora_fin', $actividad['fin'])
                    ->exists();

                if (!$yaExisteDetalle) {
                    // 4️⃣ Crear detalle de horas
                    CuadDetalleHora::create([
                        'registro_diario_id' => $registroDiario->id,
                        'actividad_id' => $actividadModelo->id,
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
