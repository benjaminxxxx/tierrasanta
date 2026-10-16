<?php

namespace App\Support;

use App\Models\VentaCochinilla;
use Exception;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DateHelper
{
    private const MESES = ['ene' => 1, 'jan' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'ago' => 8, 'aug' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12, 'dec' => 12];

    /**
     * Fecha escrita o pegada (de Excel, un correo…) a Y-m-d. Acepta 31/01/2023, 31-1-23, 31.01.2023, 2023-01-31,
     * 31-ene-2023 y el número de serie de Excel; día antes que mes. Vacío → null. Si no se entiende lanza una
     * excepción con un mensaje para el usuario (no un error de base de datos). Misma regla que resources/js/fechas.js.
     */
    public static function normalizarFecha($valor, string $campo = 'fecha'): ?string
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }
        $texto = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $valor)));
        $valida = fn($y, $m, $d) => (int) $y >= 1900 && (int) $y <= 2100 && checkdate((int) $m, (int) $d, (int) $y)
            ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
        $anio = fn($y) => strlen($y) <= 2 ? ((int) $y < 70 ? 2000 + (int) $y : 1900 + (int) $y) : (int) $y;

        $fecha = null;
        if (preg_match('/^\d{5}(\.\d+)?$/', $texto)) {
            $serie = (int) floor((float) $texto); // Excel: días desde 1899-12-30
            $fecha = $serie > 20000 && $serie < 80000 ? Carbon::create(1899, 12, 30)->addDays($serie)->format('Y-m-d') : null;
        } else {
            $texto = preg_split('/[ t](?=\d{1,2}:)/', $texto)[0]; // sin la hora
            if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $texto, $m)) {
                $fecha = $valida($m[1], $m[2], $m[3]);
            } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})$/', $texto, $m)) {
                $fecha = $valida($anio($m[3]), $m[2], $m[1]);
            } elseif (preg_match('/^(\d{1,2})[-\/. ]([a-z]{3})[a-z]*\.?[-\/. ](\d{2}|\d{4})$/u', $texto, $m) && isset(self::MESES[$m[2]])) {
                $fecha = $valida($anio($m[3]), self::MESES[$m[2]], $m[1]);
            }
        }
        if (!$fecha) {
            throw new Exception("La {$campo} \"{$valor}\" no es una fecha válida. Usa el formato día/mes/año (ej. 31/01/2023).");
        }
        return $fecha;
    }
    public static function esPeriodoVigente(string $fecha): bool
    {
        return (int) date('Y', strtotime($fecha)) === (int) date('Y');
    }
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
    public static function calcularTotalDias($fechaInicio, $fechaFin)
    {
        if (!$fechaInicio || !$fechaFin) {
            return 0;
        }

        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        // diffInDays siempre devuelve número positivo
        return $inicio->diffInDays($fin) + 1; // +1 si quieres incluir ambos días
    }
}
