<?php
namespace App\Services\GestionCuadrilla;

use Carbon\Carbon;

class DescripcionDetallePagoServicio
{
    // "{prefijo} {nombreGrupo} del {dd} al {dd} de {NombreMes}" — rango (cuadrilla normal, bonos, gastos)
    public static function generarRango(string $prefijo, string $nombreGrupo, string $fechaInicio, string $fechaFin): string
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        return sprintf(
            '%s %s del %s al %s de %s',
            $prefijo,
            $nombreGrupo,
            $inicio->format('d'),
            $fin->format('d'),
            $fin->locale('es')->translatedFormat('F')
        );
    }

    // "{prefijo} {dd-mm} {sufijo}" — un solo día (cuadrilla extras)
    public static function generarUnDia(string $prefijo, string $fecha, ?string $sufijo = null): string
    {
        $base = sprintf('%s %s', $prefijo, Carbon::parse($fecha)->format('d-m'));

        return $sufijo ? "{$base} {$sufijo}" : $base;
    }
}