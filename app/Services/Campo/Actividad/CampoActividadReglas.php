<?php

namespace App\Services\Campo\Actividad;

/**
 * Campos que no se revisan al guardar horas (etapas de los grupos de hileras, CampaniaSectorRevision).
 */
class CampoActividadReglas
{
    /**
     * Campos que no son parcelas: ahí se mezclan muchos códigos todos los días (vigilancia, cocinera, tractorista…)
     * y no tienen etapas. En minúsculas.
     */
    public const CAMPOS_SIN_REVISION = ['fdm'];

    public static function seRevisa(string $campo): bool
    {
        return !in_array(mb_strtolower(trim($campo)), self::CAMPOS_SIN_REVISION, true);
    }
}
