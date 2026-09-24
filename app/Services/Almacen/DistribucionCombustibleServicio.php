<?php

namespace App\Services\Almacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use DB;
use Illuminate\Support\Carbon;

class DistribucionCombustibleServicio
{
    public static function previsualizar(array $filas, int $salidaId): array
    {
        $salida = AlmacenProductoSalida::findOrFail($salidaId);

        // Todas las salidas de combustible de esta maquinaria (para resolver "sugerida")
        $salidas = AlmacenProductoSalida::where('maquinaria_id', $salida->maquinaria_id)
            ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
            ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
            ->orderBy('fecha_reporte')
            ->get(['id', 'fecha_reporte']);

        $preview = [];

        foreach ($filas as $i => $fila) {
            $vacia = empty($fila['fecha']) && empty($fila['campo_nombre'])
                && empty($fila['labor_diaria']) && empty($fila['hora_inicio']) && empty($fila['hora_fin']);

            if ($vacia) {
                continue; // eliminación pura: no necesita confirmación de salida
            }

            foreach (['fecha', 'campo_nombre', 'hora_inicio', 'hora_fin', 'labor_diaria'] as $campo) {
                if (empty($fila[$campo])) {
                    throw new \Exception("El campo \"{$campo}\" es obligatorio. (fila " . ($i + 1) . ")");
                }
            }
            if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                throw new \Exception("La hora de fin debe ser mayor a la de inicio. (fila " . ($i + 1) . ")");
            }

            $fechaFila = Carbon::parse($fila['fecha'])->startOfDay();

            // Salida "natural" según fecha: la más cercana con fecha_reporte <= fecha de la fila
            $sugerida = $salidas
                ->filter(fn($s) => Carbon::parse($s->fecha_reporte)->startOfDay()->lte($fechaFila))
                ->sortByDesc('fecha_reporte')
                ->first();

            if (!$sugerida) {
                throw new \Exception(
                    "La fecha {$fechaFila->format('Y-m-d')} es anterior a cualquier salida de combustible "
                    . "registrada para esta maquinaria. (fila " . ($i + 1) . ")"
                );
            }

            $coincide = $sugerida->id === $salida->id;

            $candidatas = $coincide
                ? [$salida]
                : [$sugerida, $salida]; // primero la sugerida, luego la activa

            $preview[] = [
                'fila_index' => $i,
                'fecha' => $fila['fecha'],
                'campo_nombre' => $fila['campo_nombre'],
                'labor_diaria' => $fila['labor_diaria'],
                'coincide' => $coincide,
                'salida_elegida_id' => $salida->id, // por defecto: la salida activa
                'candidatas' => collect($candidatas)->map(fn($s) => [
                    'id' => $s->id,
                    'fecha' => Carbon::parse($s->fecha_reporte)->format('d/m/Y'),
                ])->all(),
            ];
        }

        return $preview;
    }

    public static function guardarDistribuciones(array $filas, int $salidaId): array
    {
        $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];
        $salida = AlmacenProductoSalida::findOrFail($salidaId);

        DB::transaction(function () use ($filas, $salida, &$resultados) {
            foreach ($filas as $fila) {
                $id = $fila['id'] ?? null;

                $vacia = empty($fila['fecha']) && empty($fila['campo_nombre'])
                    && empty($fila['labor_diaria']) && empty($fila['hora_inicio']) && empty($fila['hora_fin']);

                if ($vacia) {
                    if ($id) {
                        DistribucionCombustible::findOrFail($id)->delete();
                        $resultados['eliminados']++;
                    }
                    continue;
                }

                foreach (['fecha', 'campo_nombre', 'hora_inicio', 'hora_fin', 'labor_diaria'] as $campo) {
                    if (empty($fila[$campo])) {
                        throw new \Exception("El campo \"{$campo}\" es obligatorio." . ($id ? " (ID: {$id})" : ''));
                    }
                }
                if (empty($fila['salida_id'])) {
                    throw new \Exception("No se indicó la salida de combustible para esta fila." . ($id ? " (ID: {$id})" : ''));
                }
                if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                    throw new \Exception("La hora de fin debe ser mayor a la de inicio." . ($id ? " (ID: {$id})" : ''));
                }

                $datos = [
                    'fecha' => $fila['fecha'],
                    'campo' => $fila['campo_nombre'],
                    'hora_inicio' => $fila['hora_inicio'],
                    'hora_salida' => $fila['hora_fin'],
                    'actividad' => $fila['labor_diaria'],
                    'maquinaria_id' => $salida->maquinaria_id,
                    'almacen_producto_salida_id' => $fila['salida_id'],
                ];

                if ($id) {
                    DistribucionCombustible::findOrFail($id)->update($datos);
                    $resultados['actualizados']++;
                } else {
                    DistribucionCombustible::create($datos);
                    $resultados['creados']++;
                }
            }
        });

        return $resultados;
    }
    public function reconciliarPorSalidas(array $salidaIds): array
    {
        $salidas = AlmacenProductoSalida::whereIn('id', $salidaIds)->get();
        $vinculadasAuto = 0;
        $pendientesManual = [];

        $porMaquinariaFecha = $salidas->groupBy(fn($s) => $s->maquinaria_id . '|' . $s->fecha_reporte->format('Y-m-d'));

        foreach ($porMaquinariaFecha as $clave => $candidatas) {
            [$maquinariaId, $fecha] = explode('|', $clave);

            $huerfanas = DistribucionCombustible::where('maquinaria_id', $maquinariaId)
                ->where('fecha', $fecha)
                ->whereNull('almacen_producto_salida_id')
                ->get();

            if ($huerfanas->isEmpty()) {
                continue;
            }

            if ($candidatas->count() === 1) {
                DistribucionCombustible::whereIn('id', $huerfanas->pluck('id'))
                    ->update(['almacen_producto_salida_id' => $candidatas->first()->id]);
                $vinculadasAuto += $huerfanas->count();
            } else {
                $pendientesManual[] = [
                    'maquinaria_id' => $maquinariaId,
                    'fecha' => $fecha,
                    'distribuciones' => $huerfanas,
                    'salidas_candidatas' => $candidatas,
                ];
            }
        }

        return compact('vinculadasAuto', 'pendientesManual');
    }
    /**
     * $salidaId: la salida a la que pertenecen TODAS las filas del modal.
     * Ya no necesitamos buscar la salida — viene fija desde el botón.
     */
    /*public static function guardarDistribuciones(array $filas, int $salidaId): array
    {
        $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

        $salida = AlmacenProductoSalida::findOrFail($salidaId);

        DB::transaction(function () use ($filas, $salida, &$resultados) {
            foreach ($filas as $fila) {
                $id = $fila['id'] ?? null;

                // ── FILA VACÍA → ELIMINAR ─────────────────────────────
                $vacia = empty($fila['fecha'])
                    && empty($fila['campo_nombre'])
                    && empty($fila['labor_diaria'])
                    && empty($fila['hora_inicio'])
                    && empty($fila['hora_fin']);

                if ($vacia) {
                    if ($id) {
                        DistribucionCombustible::findOrFail($id)->delete();
                        $resultados['eliminados']++;
                    }
                    continue;
                }

                // ── VALIDACIÓN CAMPOS REQUERIDOS ──────────────────────
                foreach (['fecha', 'campo_nombre', 'hora_inicio', 'hora_fin', 'labor_diaria'] as $campo) {
                    if (empty($fila[$campo])) {
                        throw new \Exception("El campo \"{$campo}\" es obligatorio." . ($id ? " (ID: {$id})" : ''));
                    }
                }

                if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                    throw new \Exception("La hora de fin debe ser mayor a la de inicio." . ($id ? " (ID: {$id})" : ''));
                }

                // ── RESTRICCIÓN: no debe existir salida entre la salida
                //    elegida y la fecha de la distribución ───────────────
                // Caso: salida es 01/06, distribución es 05/06
                // Si existe otra salida el 03/06 para la misma maquinaria,
                // esa distribución debería ir en la salida del 03/06, no 01/06.
                $salidaIntermedia = AlmacenProductoSalida::where('maquinaria_id', $salida->maquinaria_id)
                    ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
                    ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
                    ->where('id', '!=', $salida->id)
                    // Posterior a la salida elegida pero anterior o igual a la fecha de la distribución
                    ->whereDate('fecha_reporte', '>', $salida->fecha_reporte)
                    ->whereDate('fecha_reporte', '<', $fila['fecha'])
                    ->exists();

                if ($salidaIntermedia) {
                    throw new \Exception(
                        "La distribución del {$fila['fecha']} no puede asignarse a esta salida "
                        . "({$salida->fecha_reporte}) porque existe una salida de combustible posterior "
                        . "para esta maquinaria que la cubre."
                    );
                }

                // ── GUARDAR ───────────────────────────────────────────
                $datos = [
                    'fecha' => $fila['fecha'],
                    'campo' => $fila['campo_nombre'],
                    'hora_inicio' => $fila['hora_inicio'],
                    'hora_salida' => $fila['hora_fin'],
                    'actividad' => $fila['labor_diaria'],
                    'maquinaria_id' => $salida->maquinaria_id,
                    'almacen_producto_salida_id' => $salida->id,
                ];

                if ($id) {
                    DistribucionCombustible::findOrFail($id)->update($datos);
                    $resultados['actualizados']++;
                } else {
                    DistribucionCombustible::create($datos);
                    $resultados['creados']++;
                }
            }
        });

        return $resultados;
    }*/
        /*
    public static function guardarDistribuciones(array $filas, int $salidaId, bool $respetarSalida = false): array
    {
        $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

        $salida = AlmacenProductoSalida::findOrFail($salidaId);
        $fechaSalida = Carbon::parse($salida->fecha_reporte)->startOfDay();

        // ── Próxima salida de combustible para esta maquinaria ────────────
        $siguienteSalida = AlmacenProductoSalida::where('maquinaria_id', $salida->maquinaria_id)
            ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
            ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
            ->whereDate('fecha_reporte', '>', $fechaSalida)
            ->orderBy('fecha_reporte')
            ->first();

        $fechaSiguienteSalida = $siguienteSalida ? Carbon::parse($siguienteSalida->fecha_reporte)->startOfDay() : null;

        // Rango válido para esta salida:
        // fecha_dist >= salida.fecha  Y  fecha_dist <= siguiente_salida.fecha (si existe)
        $fechaEnRango = function (string $fechaTexto) use ($fechaSalida, $fechaSiguienteSalida): bool {
            $fecha = Carbon::parse($fechaTexto)->startOfDay();

            if ($fecha->lt($fechaSalida)) {
                return false;
            }
            // Límite superior INCLUSIVO: si la dist cae el mismo día que la
            // siguiente salida, la salidaId enviada sigue mandando.
            if ($fechaSiguienteSalida && $fecha->gt($fechaSiguienteSalida)) {
                return false;
            }
            return true;
        };

        DB::transaction(function () use ($filas, $salida, $fechaSalida, $fechaSiguienteSalida, $fechaEnRango, &$resultados, $respetarSalida) {
            foreach ($filas as $fila) {
                $id = $fila['id'] ?? null;

                $vacia = empty($fila['fecha'])
                    && empty($fila['campo_nombre'])
                    && empty($fila['labor_diaria'])
                    && empty($fila['hora_inicio'])
                    && empty($fila['hora_fin']);

                if ($vacia) {
                    if ($id) {
                        DistribucionCombustible::findOrFail($id)->delete();
                        $resultados['eliminados']++;
                    }
                    continue;
                }

                foreach (['fecha', 'campo_nombre', 'hora_inicio', 'hora_fin', 'labor_diaria'] as $campo) {
                    if (empty($fila[$campo])) {
                        throw new \Exception("El campo \"{$campo}\" es obligatorio." . ($id ? " (ID: {$id})" : ''));
                    }
                }

                if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                    throw new \Exception("La hora de fin debe ser mayor a la de inicio." . ($id ? " (ID: {$id})" : ''));
                }

                $fechaFila = Carbon::parse($fila['fecha'])->startOfDay();

                if ($id) {
                    // ── EDITAR ────────────────────────────────────────────
                    if ($fechaEnRango($fila['fecha']) || $respetarSalida) {

                        $salidaDestino = $salida;
                    } else {
                        $salidaDestino = AlmacenProductoSalida::where('maquinaria_id', $salida->maquinaria_id)
                            ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
                            ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
                            ->whereDate('fecha_reporte', '<=', $fechaFila)
                            ->orderByDesc('fecha_reporte')
                            ->first();

                        if (!$salidaDestino) {
                            throw new \Exception(
                                "No existe una salida de combustible válida para la fecha {$fechaFila->format('Y-m-d')} "
                                . "en esta maquinaria. (ID: {$id})"
                            );
                        }

                        $fechaSalidaDestino = Carbon::parse($salidaDestino->fecha_reporte)->startOfDay();

                        if ($fechaFila->lt($fechaSalidaDestino)) {
                            throw new \Exception(
                                "La fecha {$fechaFila->format('Y-m-d')} es anterior a cualquier salida de combustible "
                                . "registrada para esta maquinaria. (ID: {$id})"
                            );
                        }
                    }

                    DistribucionCombustible::findOrFail($id)->update([
                        'fecha' => $fila['fecha'],
                        'campo' => $fila['campo_nombre'],
                        'hora_inicio' => $fila['hora_inicio'],
                        'hora_salida' => $fila['hora_fin'],
                        'actividad' => $fila['labor_diaria'],
                        'maquinaria_id' => $salida->maquinaria_id,
                        'almacen_producto_salida_id' => $salidaDestino->id,
                    ]);
                    $resultados['actualizados']++;

                } else {
                    // ── INSERTAR ──────────────────────────────────────────
                    if (!$fechaEnRango($fila['fecha'])) {
                        if ($fechaFila->lt($fechaSalida)) {
                            throw new \Exception(
                                "La fecha {$fechaFila->format('Y-m-d')} es anterior a esta salida ({$fechaSalida->format('Y-m-d')}). "
                                . "No se puede registrar una distribución con fecha previa a la salida."
                            );
                        }
                        if (!$respetarSalida) {
                            throw new \Exception(
                                "La fecha {$fechaFila->format('Y-m-d')} pertenece a la salida del {$fechaSiguienteSalida->format('Y-m-d')}, "
                                . "no a esta ({$fechaSalida->format('Y-m-d')}). "
                                . "Regístrela desde esa salida."
                            );
                        }
                    }

                    DistribucionCombustible::create([
                        'fecha' => $fila['fecha'],
                        'campo' => $fila['campo_nombre'],
                        'hora_inicio' => $fila['hora_inicio'],
                        'hora_salida' => $fila['hora_fin'],
                        'actividad' => $fila['labor_diaria'],
                        'maquinaria_id' => $salida->maquinaria_id,
                        'almacen_producto_salida_id' => $salida->id,
                    ]);
                    $resultados['creados']++;
                }
            }
        });

        return $resultados;
    }*/
    /*
public static function guardarDistribuciones(array $filas, int $salidaId, bool $respetarSalida = false): array
{
    $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

    $salida = AlmacenProductoSalida::findOrFail($salidaId);

    // ── Próxima salida de combustible para esta maquinaria ────────────
    // Define el límite superior (exclusivo) del rango de esta salida.
    $siguienteSalida = AlmacenProductoSalida::where('maquinaria_id', $salida->maquinaria_id)
        ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
        ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
        ->whereDate('fecha_reporte', '>', $salida->fecha_reporte)
        ->orderBy('fecha_reporte')
        ->first();

    // Rango válido para esta salida:
    // fecha_dist >= salida.fecha  Y  fecha_dist < siguiente_salida.fecha (si existe)
    $fechaEnRango = function (string $fecha) use ($salida, $siguienteSalida): bool {
        if ($fecha < $salida->fecha_reporte) {
            return false;
        }
        // Límite superior INCLUSIVO: si la dist cae el mismo día que la
        // siguiente salida, la salidaId enviada sigue mandando.
        // Solo sale del rango si la fecha es ESTRICTAMENTE mayor a la siguiente.
        if ($siguienteSalida && $fecha > $siguienteSalida->fecha_reporte) {
            return false;
        }
        return true;
    };

    DB::transaction(function () use ($filas, $salida, $siguienteSalida, $fechaEnRango, &$resultados, $respetarSalida) {
        foreach ($filas as $fila) {
            $id = $fila['id'] ?? null;

            // ── FILA VACÍA → ELIMINAR ─────────────────────────────
            $vacia = empty($fila['fecha'])
                && empty($fila['campo_nombre'])
                && empty($fila['labor_diaria'])
                && empty($fila['hora_inicio'])
                && empty($fila['hora_fin']);

            if ($vacia) {
                if ($id) {
                    DistribucionCombustible::findOrFail($id)->delete();
                    $resultados['eliminados']++;
                }
                continue;
            }

            // ── VALIDACIÓN CAMPOS REQUERIDOS ──────────────────────
            foreach (['fecha', 'campo_nombre', 'hora_inicio', 'hora_fin', 'labor_diaria'] as $campo) {
                if (empty($fila[$campo])) {
                    throw new \Exception("El campo \"{$campo}\" es obligatorio." . ($id ? " (ID: {$id})" : ''));
                }
            }

            if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                throw new \Exception("La hora de fin debe ser mayor a la de inicio." . ($id ? " (ID: {$id})" : ''));
            }

            if ($id) {
                // ── EDITAR ────────────────────────────────────────────
                // La salidaId enviada manda: si la nueva fecha cae dentro
                // del rango de esta salida, se asigna a ella sin importar
                // que exista otra salida con la misma fecha.
                // Si la nueva fecha cae fuera del rango, buscamos la salida
                // correcta. Si no existe ninguna válida → error.
                if ($fechaEnRango($fila['fecha']) || $respetarSalida) {

                    $salidaDestino = $salida;
                } else {
                    // Buscar la salida a la que realmente pertenece la nueva fecha
                    $salidaDestino = AlmacenProductoSalida::where('maquinaria_id', $salida->maquinaria_id)
                        ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
                        ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
                        ->whereDate('fecha_reporte', '<=', $fila['fecha'])
                        ->orderByDesc('fecha_reporte')
                        ->first();

                    if (!$salidaDestino) {
                        throw new \Exception(
                            "No existe una salida de combustible válida para la fecha {$fila['fecha']} "
                            . "en esta maquinaria. (ID: {$id})"
                        );
                    }

                    // Verificar que la salida encontrada no sea la misma enviada
                    // (evitar reasignación circular cuando el rango fue recalculado)
                    // y que la fecha no caiga en un hueco sin salida previa

                    if ($fila['fecha'] < $salidaDestino->fecha_reporte) {
                        throw new \Exception(
                            "La fecha {$fila['fecha']} es anterior a cualquier salida de combustible "
                            . "registrada para esta maquinaria. (ID: {$id})"
                        );
                    }
                }

                DistribucionCombustible::findOrFail($id)->update([
                    'fecha' => $fila['fecha'],
                    'campo' => $fila['campo_nombre'],
                    'hora_inicio' => $fila['hora_inicio'],
                    'hora_salida' => $fila['hora_fin'],
                    'actividad' => $fila['labor_diaria'],
                    'maquinaria_id' => $salida->maquinaria_id,
                    'almacen_producto_salida_id' => $salidaDestino->id,
                ]);
                $resultados['actualizados']++;

            } else {
                // ── INSERTAR ──────────────────────────────────────────
                // La fecha DEBE caer dentro del rango de la salida enviada.
                // La salidaId manda: si el usuario está en la salida del día 2
                // y registra una dist del día 5 (donde también hay salida),
                // es válido porque el rango del 2 cubre hasta antes del siguiente.
                // Si la fecha cae fuera del rango → error descriptivo.
                if (!$fechaEnRango($fila['fecha'])) {
                    //dd($fila['fecha'],$salida->fecha_reporte);//uno es string y el otro es cast date:Y-m-d
                    if ($fila['fecha'] < $salida->fecha_reporte) {
                        throw new \Exception(
                            "La fecha {$fila['fecha']} es anterior a esta salida ({$salida->fecha_reporte}). "
                            . "No se puede registrar una distribución con fecha previa a la salida."
                        );
                    }
                    if (!$respetarSalida) {
                        throw new \Exception(
                            "La fecha {$fila['fecha']} pertenece a la salida del {$siguienteSalida->fecha_reporte}, "
                            . "no a esta ({$salida->fecha_reporte}). "
                            . "Regístrela desde esa salida."
                        );
                    }

                }

                DistribucionCombustible::create([
                    'fecha' => $fila['fecha'],
                    'campo' => $fila['campo_nombre'],
                    'hora_inicio' => $fila['hora_inicio'],
                    'hora_salida' => $fila['hora_fin'],
                    'actividad' => $fila['labor_diaria'],
                    'maquinaria_id' => $salida->maquinaria_id,
                    'almacen_producto_salida_id' => $salida->id,
                ]);
                $resultados['creados']++;
            }
        }
    });

    return $resultados;
}*/
}