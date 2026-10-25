<?php

namespace App\Services\Planilla;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportarConceptosProceso
{
    public static function ejecutar(array $filas)
    {
        return DB::transaction(function () use ($filas) {
            $idsParaMantener = [];
            $errores = [];

            foreach ($filas as $index => $fila) {
                try {
                    // --- PARSEO Y LIMPIEZA DE DATOS ---

                    // 1. Convertir strings vacíos a NULL (Crucial para fechas)
                    foreach ($fila as $key => $value) {
                        if (is_string($value) && trim($value) === '') {
                            $fila[$key] = null;
                        }
                    }

                    // 2. Normalizar Booleanos (Evita errores de '0', 'false', o vacíos)
                    $fila['incluye_igv'] = filter_var($fila['incluye_igv'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $fila['activo'] = filter_var($fila['activo'] ?? true, FILTER_VALIDATE_BOOLEAN);

                    // 3. Asegurar que el ID sea null si es string vacío
                    $id = !empty($fila['id']) ? (int) $fila['id'] : null;

                    // --- PROCESO DE GUARDADO ---
                    $concepto = ConceptoPlanillaServicio::guardar($fila, $id);

                    if ($concepto && $concepto->id) {
                        $idsParaMantener[] = $concepto->id;
                    }

                } catch (ValidationException $e) {
                    $errores["Fila " . ($index + 1)] = $e->errors();
                }
            }

            if (!empty($errores)) {
                throw ValidationException::withMessages(['importacion' => $errores]);
            }

            ConceptoPlanillaServicio::eliminarExcepto($idsParaMantener);

            return true;
        });
    }
}