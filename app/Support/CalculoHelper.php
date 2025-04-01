<?php

namespace App\Support;

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
   
}
