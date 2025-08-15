<?php

namespace App\Support;

use App\Models\VentaCochinilla;
use Exception;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DateHelper
{
    public static function convertirHorasADecimal(string $hora): float
    {
        [$horas, $minutos, $segundos] = explode(':', $hora);
        return (int) $horas + ((int) $minutos / 60) + ((int) $segundos / 3600);
    }
    /**
     * Limpia y formatea una cadena de texto para que represente un tiempo válido en formato HH:MM.
     * Si no es válida, retorna '00:00'.
     */
    public static function formatearHorasDesdeTexto(?string $valor): string
    {
        $valor = isset($valor) ? trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $valor)) : '00:00';

        // Reemplazar punto por dos puntos (por si ingresaron "12.30" en vez de "12:30")
        $valor = str_replace('.', ':', $valor);

        // Si es solo un número (ej. "3") asumimos que es "3:00"
        if (preg_match('/^\d+$/', $valor)) {
            $valor .= ':00';
        }
        // Si es del tipo "3:5", completar con ceros para que sea "03:05"
        elseif (preg_match('/^\d+:\d$/', $valor)) {
            [$hours, $minutes] = explode(':', $valor);
            $valor = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_RIGHT);
        }

        // Validar formato final
        if (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $valor)) {
            return '00:00';
        }

        // Asegurar formato HH:MM
        return date('H:i', strtotime($valor));
    }
    public static function fechasCoinciden($fecha1, $fecha2)
    {
        try {
            $f1 = Carbon::parse(FormatoHelper::parseFecha($fecha1))->format('Y-m-d');
            $f2 = Carbon::parse(FormatoHelper::parseFecha($fecha2))->format('Y-m-d');
            return $f1 === $f2;
        } catch (\Exception $e) {
            return false;
        }
    }
    public static function calcularDuracionPorTramo($horarios)
    {
        $tramos = explode(',', $horarios);
        $duraciones = [];

        foreach ($tramos as $tramo) {
            $tramo = trim($tramo);

            if (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $tramo, $matches)) {
                try {
                    $inicio = \Carbon\Carbon::createFromFormat('H:i', $matches[1]);
                    $fin = \Carbon\Carbon::createFromFormat('H:i', $matches[2]);

                    // Evitar errores si fin < inicio
                    if ($fin->lt($inicio)) {
                        $fin->addDay(); // por si hay tramos tipo 22:00-02:00
                    }

                    $minutos = $inicio->diffInMinutes($fin);
                    $horas = floor($minutos / 60);
                    $minutosRestantes = $minutos % 60;
                    $duraciones[] = sprintf('%02d:%02d', $horas, $minutosRestantes);
                } catch (Exception $e) {
                    $duraciones[] = '00:00';
                }
            } else {
                $duraciones[] = '00:00';
            }
        }

        return implode(',', $duraciones); // Ej: "05:00,03:00"
    }
    public static function calcularTotalHorasFloat($horarios)
    {
        $tramos = explode(',', $horarios);
        $totalHoras = 0;

        foreach ($tramos as $tramo) {
            $tramo = trim($tramo);

            if (preg_match('/^(\d{2}):(\d{2})$/', $tramo, $matches)) {
                $horas = (int) $matches[1];
                $minutos = (int) $matches[2];
                $totalHoras += $horas + ($minutos / 60);
            }
        }

        return round($totalHoras, 2); // ej: 8.5
    }
}
