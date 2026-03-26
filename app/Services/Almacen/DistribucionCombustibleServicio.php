<?php

namespace App\Services\Almacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use DB;

class DistribucionCombustibleServicio
{
    public static function guardarDistribuciones(array $filas): array
    {
        $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0];

        DB::transaction(function () use ($filas, &$resultados) {
            foreach ($filas as $fila) {

                // Ignorar filas de cabecera de salida
                if ($fila['es_salida'] ?? false) continue;

                $id = $fila['id'] ?? null;

                // Detectar fila vacía → eliminar si tenía id
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

                // Validar campos mínimos
                foreach (['fecha', 'campo_nombre', 'hora_inicio', 'hora_fin', 'labor_diaria', 'maquinaria_id'] as $campo) {
                    if (empty($fila[$campo])) {
                        throw new \Exception("El campo \"{$campo}\" es obligatorio." . ($id ? " (ID: {$id})" : ''));
                    }
                }

                if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                    throw new \Exception("La hora de fin debe ser posterior a la de inicio." . ($id ? " (ID: {$id})" : ''));
                }

                // Buscar la salida correspondiente:
                // La más reciente con fecha_reporte <= fecha de la distribución
                // Si hay salida el mismo día, esa tiene prioridad (desc limit 1 la trae primero)
                $salida = AlmacenProductoSalida::whereDate('fecha_reporte', '<=', $fila['fecha'])
                    ->where('maquinaria_id', $fila['maquinaria_id'])
                    ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
                    ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''))
                    ->orderBy('fecha_reporte', 'desc')
                    ->first();

                if (!$salida) {
                    throw new \Exception(
                        "No se encontró una salida de combustible para la maquinaria ID {$fila['maquinaria_id']} "
                        . "en o antes de la fecha {$fila['fecha']}."
                    );
                }

                $datos = [
                    'fecha'                      => $fila['fecha'],
                    'campo'                      => $fila['campo_nombre'],
                    'hora_inicio'                => $fila['hora_inicio'],
                    'hora_salida'                => $fila['hora_fin'],
                    'actividad'                  => $fila['labor_diaria'],
                    'maquinaria_id'              => $fila['maquinaria_id'],
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