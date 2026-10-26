<?php

namespace App\Services\Costos\Mensual;

/**
 * Conceptos del costo mensual (Costos → Mensual) y su columna en costos_mensuales / costo_mensual_distribuciones.
 * Cada concepto tiene parte blanco y negro: {columna}_blanco, {columna}_negro.
 */
class CostosMensualReglas
{
    public const FIJO = 'fijo';
    public const OPERATIVO = 'operativo';

    /** columna => [tipo, nombre] en el orden del reporte */
    public const CONCEPTOS = [
        'fijo_administrativo' => [self::FIJO, 'Costo administrativo'],
        'fijo_financiero' => [self::FIJO, 'Costo financiero'],
        'fijo_gastos_oficina' => [self::FIJO, 'Gastos de oficina'],
        'fijo_depreciaciones' => [self::FIJO, 'Depreciaciones'],
        'fijo_costo_terreno' => [self::FIJO, 'Costo terreno'],
        'operativo_servicios_fundo' => [self::OPERATIVO, 'Servicios fundo'],
        'operativo_mano_obra_indirecta' => [self::OPERATIVO, 'Mano de obra indirecta'],
    ];

    /** Se calcula en /costos/mano-obra-indirecta: si ya se calculó, no se edita a mano. */
    public const MOI = 'operativo_mano_obra_indirecta';

    public static function nombreTipo(string $tipo): string
    {
        return $tipo === self::FIJO ? 'Costo fijo' : 'Costo operativo';
    }
}
