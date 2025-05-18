<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CalculoHelper
{
    /**
     * Calcula la cantidad de jornales en base a las horas trabajadas.
     *
     * @param float|int $totalDeHoras Horas totales trabajadas
     * @return float Cantidad de jornales calculados
     * @throws InvalidArgumentException Si el valor ingresado no es válido
     */
    public static function calcularJornales(float|int $totalDeHoras): float
    {
        if (!is_numeric($totalDeHoras) || $totalDeHoras < 0) {
            throw new InvalidArgumentException("El total de horas debe ser un número positivo.");
        }

        return $totalDeHoras != 0 ? (float) (8 / $totalDeHoras) : 0;
    }
    /**
     * Calcula la duración entre dos fechas y la devuelve en formato legible
     * (ej: "1 año, 2 meses, 3 días").
     *
     * Ambas fechas deben estar en un formato aceptado por Carbon (string o DateTime).
     * Si alguna de las fechas es nula o inválida, retorna null.
     *
     * @param string|null $inicio Fecha de inicio (ej. fecha de infestación)
     * @param string|null $fin    Fecha de fin (ej. fecha de cosecha)
     * @return string|null Duración legible (años, meses y días) o null si no se puede calcular
     */
    public static function calcularDuracionEntreFechas(?string $inicio, ?string $fin): ?string
    {
        if (!$inicio || !$fin) {
            return null;
        }

        $inicio = Carbon::parse($inicio);
        $fin = Carbon::parse($fin);

        $diferencia = $inicio->diff($fin);

        return $diferencia->y . ' año' . ($diferencia->y !== 1 ? 's' : '') . ', '
            . $diferencia->m . ' mes' . ($diferencia->m !== 1 ? 'es' : '') . ', '
            . $diferencia->d . ' día' . ($diferencia->d !== 1 ? 's' : '');
    }

}
