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
