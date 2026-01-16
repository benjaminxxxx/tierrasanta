<?php

namespace App\Support;

class SugerenciaHelper
{
    /**
     * Sugiere el siguiente nombre de campaña incrementando el último número encontrado.
     * Ejemplo: "T.2025" -> "T.2026", "Camp-01" -> "Camp-02", "Verano 2024" -> "Verano 2025"
     * * @param string $nombreCampaniaAnterior
     * @return string
     */
    public static function sugerirSiguienteNombreCampania(string $nombreCampaniaAnterior = ''): string
    {
        // 1. Si está vacío, sugerimos un formato estándar con el año actual
        if (empty(trim($nombreCampaniaAnterior))) {
            return 'C.' . date('Y');
        }

        // 2. Expresión regular para encontrar el ÚLTIMO grupo de dígitos en la cadena
        // (\d+) captura los dígitos
        // (?!.*\d) asegura que sea el último grupo de números (que no haya más números después)
        $pattern = '/(\d+)(?!.*\d)/';

        $nuevoNombre = preg_replace_callback($pattern, function ($matches) {
            $numeroActual = $matches[1];
            $nuevoNumero = (int)$numeroActual + 1;

            // Mantenemos el formato original (ej. si era 01, que pase a 02)
            // usando str_pad para rellenar con ceros a la izquierda si es necesario
            return str_pad($nuevoNumero, strlen($numeroActual), '0', STR_PAD_LEFT);
        }, $nombreCampaniaAnterior, 1, $count);

        // 3. Si no se encontró ningún número en la cadena original ($count == 0)
        // Simplemente añadimos el año actual como sufijo
        if ($count === 0) {
            return $nombreCampaniaAnterior . '.' . (date('Y'));
        }

        return $nuevoNombre;
    }
}
