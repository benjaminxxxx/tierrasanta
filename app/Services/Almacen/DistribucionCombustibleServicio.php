<?php

namespace App\Services\Almacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use DB;

class DistribucionCombustibleServicio
{
    /**
     * $salidaId: la salida a la que pertenecen TODAS las filas del modal.
     * Ya no necesitamos buscar la salida — viene fija desde el botón.
     */
    public static function guardarDistribuciones(array $filas, int $salidaId): array
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
    }
}