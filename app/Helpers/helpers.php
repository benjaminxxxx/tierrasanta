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
    /**
     * Formatea un número redondeando a X decimales.
     *
     * $decimales = 2 por defecto (comportamiento tradicional)
     * $rellenarCeros = true => number_format tradicional
     * $rellenarCeros = false => elimina ceros extra a la derecha
     *
     * Ejemplos:
     * formatear_numero(5.2)                      -> "5.20"
     * formatear_numero(5.2, 4, false)           -> "5.2"
     * formatear_numero(5.23456, 4, false)       -> "5.2346"
     * formatear_numero(5.200, 4, false)         -> "5.2"
     */
    function formatear_numero($numero, $decimales = 2, $rellenarCeros = true)
    {
        if (!is_numeric($numero)) return null;

        // Redondeo al límite solicitado
        $redondeado = round($numero, $decimales);

        // Formato tradicional con ceros
        $formateado = number_format($redondeado, $decimales, '.', '');

        if ($rellenarCeros) {
            return $formateado;
        }

        // Remove trailing zeros and decimal point only if needed
        return rtrim(rtrim($formateado, '0'), '.');
    }
}