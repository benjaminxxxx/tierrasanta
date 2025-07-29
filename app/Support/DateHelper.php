<?php

namespace App\Support;

use App\Models\VentaCochinilla;
use Exception;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DateHelper
{
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
