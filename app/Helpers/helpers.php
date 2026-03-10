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
if (!function_exists('formatear_tiempo')) {
    function formatear_tiempo($tiempo, $formato = 'H:i')
    {
        if (!$tiempo) return null;

        try {
            return Carbon::parse($tiempo)->format($formato);
        } catch (\Exception $e) {
            return $tiempo; // Por si no es un tiempo válido
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

if (!function_exists('formatear_minutos_horas')) {

    /**
     * Convierte minutos enteros en formato legible:
     *  - 0        → "00:00"
     *  - 15       → "15 minutos"
     *  - 60       → "1 hora"
     *  - 75       → "1 hora y 15 minutos"
     *  - 240      → "4 horas"
     */
    function formatear_minutos_horas($minutos)
    {
        $minutos = (int) $minutos;

        if ($minutos <= 0) {
            return "00:00";
        }

        $horas = intdiv($minutos, 60);
        $restantes = $minutos % 60;

        $texto = '';

        // Horas
        if ($horas > 0) {
            $texto .= $horas . ' ' . ($horas === 1 ? 'hora' : 'horas');
        }

        // Minutos
        if ($restantes > 0) {
            if ($horas > 0) {
                $texto .= ' y ';
            }
            $texto .= $restantes . ' ' . ($restantes === 1 ? 'minuto' : 'minutos');
        }

        return $texto;
    }
}