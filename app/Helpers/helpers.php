<?php

use Carbon\Carbon;

if (!function_exists('formatear_fecha')) {
    function formatear_fecha($fecha, $formato = 'd/m/Y') {
        if (!$fecha) return null;

        try {
            return Carbon::parse($fecha)->format($formato);
        } catch (\Exception $e) {
            return $fecha; // Por si no es una fecha válida
        }
    }
}
if (!function_exists('formatear_numero')) {
    function formatear_numero($numero) {
        if (!is_numeric($numero)) return null;

        return number_format((float) $numero, 2);
    }
}
