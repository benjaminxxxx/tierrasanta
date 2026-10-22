<?php

namespace App\Services\Sistema\LibroExcel;

use App\Exceptions\ErrorControlado;

/**
 * Libros Excel del sistema (los del escritorio de archivos del inicio y los botones "Recursos" de cada página).
 * clave => [definición, ícono]. El orden es el del escritorio.
 */
class SistemaLibroExcelRegistro
{
    public const LIBROS = [
        'recursos' => \App\Services\Sistema\Recursos\SistemaRecursosDefinicion::class,
        'insumos' => \App\Services\Almacen\Insumos\AlmacenInsumosDefinicion::class,
        'empleados' => \App\Services\Planilla\Empleados\PlanillaEmpleadosDefinicion::class,
    ];

    public static function definicion(string $clave): SistemaLibroExcelDefinicion
    {
        $clase = self::LIBROS[$clave] ?? throw new ErrorControlado("No existe el libro Excel \"{$clave}\".");
        return app($clase);
    }

    /** @return array<string, SistemaLibroExcelDefinicion> los que existen (a medida que se agregan libros) */
    public static function disponibles(): array
    {
        return collect(self::LIBROS)->filter(fn($c) => class_exists($c))->map(fn($c) => app($c))->all();
    }
}
