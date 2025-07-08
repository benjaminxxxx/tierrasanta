<?php

namespace App\Services\Cuadrilla;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuadDetalleHora;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\CuadrilleroActividad;
use App\Models\Labores;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Str;

class CuadrilleroServicio
{
    public static function guardarDesdeHandsontable($fecha, $rows)
    {
        DB::beginTransaction();
        try {
            if (!$fecha) {
                throw ValidationException::withMessages([
                    'fecha' => 'Debe especificar una fecha.'
                ]);
            }

            $usuarioId = Auth::id();
            $errores = [];

            foreach ($rows as $i => $fila) {
                // 🟡 1. Ignorar si no tiene ningún horario válido
                $tieneTramos = false;
                $tramos = [];

                for ($j = 1; $j <= 10; $j++) {
                    $inicio = $fila["hora_inicio_$j"] ?? null;
                    $fin = $fila["hora_fin_$j"] ?? null;
                    $campo = $fila["campo_$j"] ?? null;
                    $labor = $fila["labor_$j"] ?? null;

                    // Solo procesar si alguno tiene valor
                    if ($inicio || $fin || $campo || $labor) {
                        if (!$inicio || !$fin || !$labor) {
                            $errores[] = "Fila " . ($i + 1) . ", tramo $j: falta hora o labor.";
                            continue;
                        }

                        $tieneTramos = true;
                        $tramos[] = compact('inicio', 'fin', 'campo', 'labor');
                    }
                }

                if (!$tieneTramos) {
                    // Si hay cuadrillero_id registrado, pero sin tramos → eliminar todo de ese día
                    if (!empty($fila['cuadrillero_id'])) {
                        $registro = CuadRegistroDiario::where('cuadrillero_id', $fila['cuadrillero_id'])
                            ->where('fecha', $fecha)
                            ->first();

                        if ($registro) {
                            $registro->detalleHoras()->delete();
                            $registro->delete();
                        }
                    }
                    continue;
                }

                // 🔎 2. Obtener cuadrillero
                $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                if (!$cuadrilleroId && filled($fila['cuadrillero_nombres'])) {
                    $cuadrillero = Cuadrillero::whereRaw("LOWER(TRIM(nombres)) = ?", [strtolower(trim($fila['cuadrillero_nombres']))])->first();
                    if ($cuadrillero) {
                        $cuadrilleroId = $cuadrillero->id;
                    } else {
                        $cuadrillero = Cuadrillero::create([
                            'nombres' => $fila['cuadrillero_nombres'],
                            'dni' => $fila['cuadrillero_dni'] ?? null,
                            'activo' => true,
                        ]);
                        $cuadrilleroId = $cuadrillero->id;
                    }
                }

                if (!$cuadrilleroId) {
                    $errores[] = "Fila " . ($i + 1) . ": no se pudo asociar a ningún cuadrillero.";
                    continue;
                }

                // 🟢 3. Crear o actualizar registro diario
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
                    $actividad = Actividad::firstOrCreate(
                        [
                            'fecha' => $fecha,
                            'campo' => $tramo['campo'],
                            'labor_id' => $tramo['labor'],
                        ],
                        [
                            'nombre_labor' => self::getLaborNombre($tramo['labor']),
                            'codigo_labor' => self::getLaborCodigo($tramo['labor']),
                            'created_by' => $usuarioId,
                        ]
                    );

                    // Buscar si ya existe
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

                // 🔴 4. Eliminar detalles que ya no existen
                CuadDetalleHora::where('registro_diario_id', $registro->id)
                    ->whereNotIn('id', $idsDetallesNuevos)
                    ->delete();
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
