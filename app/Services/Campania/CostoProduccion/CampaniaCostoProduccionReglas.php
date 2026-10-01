<?php

namespace App\Services\Campania\CostoProduccion;

/**
 * Estructura del reporte "Costos de producción" de una campaña y en qué sección cae cada origen de
 * resumen_costo_diarios. Montos por hectárea en US$: costo (S/) / tipo de cambio / área de la campaña.
 *
 * - Costos de producción = 1.1 a 1.4.
 * - Costo operativo = mano de obra indirecta + costos operativos (servicios fundo).
 * - Costo fijo = gastos generales fijos (administrativo, financiero, oficina…), por categoría.
 * - Total campaña = producción + operativo + fijo.
 */
class CampaniaCostoProduccionReglas
{
    public const PRODUCCION = 'produccion';
    public const OPERATIVO = 'operativo';
    public const FIJO = 'fijo';

    /**
     * clave => [número, título, bloque, orígenes, tipo de cantidad (jornales | insumo | null)]
     */
    public const SECCIONES = [
        'mano_obra' => ['1.1', 'MANO DE OBRA', self::PRODUCCION,
            ['planilla', 'cuadrilla', 'planilla_bono_productividad', 'cuadrilla_bono', 'riego'], 'jornales'],
        'maquinaria' => ['1.2', 'MAQUINARIA', self::PRODUCCION, ['maquinaria', 'servicio_campo'], null],
        'fertilizante' => ['1.3', 'FERTILIZANTES', self::PRODUCCION, ['fertilizante'], 'insumo'],
        'pesticida' => ['1.4', 'PESTICIDAS', self::PRODUCCION, ['pesticida'], 'insumo'],
        'costo_operativo' => ['', 'COSTO OPERATIVO', self::OPERATIVO, ['mano_obra_indirecta', 'costo_operativo'], null],
        'costo_fijo' => ['', 'COSTO FIJO', self::FIJO, ['costo_fijo'], null],
        // Orígenes nuevos que todavía no tienen sección: se muestran para que el total cuadre
        'otros' => ['', 'OTROS COSTOS', self::OPERATIVO, [], null],
    ];

    public const SIN_MANO_OBRA = 'Sin mano de obra asignada';

    /** Orden de los grupos de mano de obra (orden del ciclo; los no listados van al final, por nombre). */
    public const ORDEN_MANO_OBRA = [
        'preparacion_terreno', 'siembra', 'sanidad', 'labores_culturales', 'riego_fertilizacion',
        'infestacion', 'reinfestacion', 'precosecha', 'cosecha', 'postcosecha', 'labores_mantenimiento',
        'fdm', 'naranja',
    ];

    /**
     * Orden de las partidas de costo operativo y costo fijo. Son los conceptos que contabilidad registra en
     * /costos/mensual (CostoMensualDistribucion), prorrateados entre campañas y guardados en la BDD con el
     * último día activo del mes (DataCostoServicio). Las no listadas van al final, por nombre.
     */
    public const ORDEN_PARTIDAS = [
        'MANO DE OBRA INDIRECTA', 'SERVICIOS FUNDO',
        'COSTO ADMINISTRATIVO', 'COSTO FINANCIERO', 'GASTOS OFICINA', 'COSTO TERRENO', 'DEPRECIACIONES',
    ];

    /**
     * Nombre de la partida del origen mano_obra_indirecta (calculado por el sistema: pagos de planilla sin trabajo
     * en campo). Es distinto del concepto "MANO DE OBRA INDIRECTA" que registra contabilidad en /costos/mensual.
     */
    public const PARTIDA_MOI_PLANILLA = 'Planilla sin labor en campo (feriados, vacaciones, licencias…)';

    /**
     * Grupo de mano de obra para los costos sin código de labor: vienen del registro de riego (tipo de labor libre)
     * y no coinciden por nombre con ninguna labor.
     */
    public const MANO_OBRA_RIEGO = 'riego_fertilizacion';

    /** Sección de un origen de resumen_costo_diarios. */
    public static function seccionDeOrigen(?string $origen): string
    {
        foreach (self::SECCIONES as $clave => [, , , $origenes]) {
            if (in_array($origen, $origenes, true)) {
                return $clave;
            }
        }
        return 'otros';
    }
}
