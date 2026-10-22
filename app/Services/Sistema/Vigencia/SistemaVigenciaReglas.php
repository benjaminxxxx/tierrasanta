<?php

namespace App\Services\Sistema\Vigencia;

use App\Exceptions\ErrorControlado;
use Illuminate\Support\Carbon;

/**
 * Vigencia del sistema (SISTEMA_VIGENCIA_DESDE en el .env): fecha desde la que trabaja esta empresa en este dominio.
 * Evita errores como cargar en el dominio nuevo un ingreso de cochinilla de agosto. Sin la variable (dominio antiguo)
 * no se bloquea nada.
 */
class SistemaVigenciaReglas
{
    public static function desde(): ?string
    {
        $valor = config('system.vigencia_desde');
        return $valor ? Carbon::parse($valor)->toDateString() : null;
    }

    /** Primer año de las listas de años: el de la vigencia, o el que la lista usaba (sin vigencia, en el dominio antiguo). */
    public static function anioMinimo(int $porDefecto): int
    {
        $desde = self::desde();
        return $desde ? max($porDefecto, (int) substr($desde, 0, 4)) : $porDefecto;
    }

    public static function permite(Carbon|string|null $fecha): bool
    {
        $desde = self::desde();
        return !$desde || !$fecha || Carbon::parse($fecha)->toDateString() >= $desde;
    }

    /** @throws ErrorControlado si la fecha es anterior a la vigencia */
    public static function validar(Carbon|string|null $fecha, string $que = 'registro'): void
    {
        if (!self::permite($fecha)) {
            throw new ErrorControlado("No se puede guardar un {$que} del " . Carbon::parse($fecha)->format('d/m/Y')
                . '. Este sistema trabaja desde el ' . Carbon::parse(self::desde())->format('d/m/Y') . '.');
        }
    }
}
