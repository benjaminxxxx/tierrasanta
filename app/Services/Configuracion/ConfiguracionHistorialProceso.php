<?php

namespace App\Services\Configuracion;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfiguracionHistorialProceso
{
    public static function ejecutar(array $filas)
    {
        return DB::transaction(function () use ($filas) {
            $idsParaMantener = [];
            $errores = [];

            foreach ($filas as $index => $fila) {
                try {
                    // --- NORMALIZACIÓN DE DATOS ---
                    foreach ($fila as $key => $value) {
                        if (is_string($value) && trim($value) === '') {
                            $fila[$key] = null;
                        }
                    }

                    // Normalizar numérico
                    if (isset($fila['valor'])) {
                        $fila['valor'] = is_numeric($fila['valor']) ? (float)$fila['valor'] : null;
                    }

                    // Asegurar formato final del ID
                    $id = !empty($fila['id']) ? (int)$fila['id'] : null;

                    // --- PROCESO DE GUARDADO ---
                    $registro = ConfiguracionHistorialServicio::guardar($fila, $id);

                    if ($registro && $registro->id) {
                        $idsParaMantener[] = $registro->id;
                    }

                } catch (ValidationException $e) {
                    $errores["Fila " . ($index + 1)] = $e->errors();
                }
            }

            // Si hay errores, se aborta
            if (!empty($errores)) {
                throw ValidationException::withMessages(['importacion' => $errores]);
            }

            // Eliminar los registros no enviados
            ConfiguracionHistorialServicio::eliminarExcepto($idsParaMantener);

            return true;
        });
    }
}