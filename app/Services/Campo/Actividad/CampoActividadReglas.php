<?php

namespace App\Services\Campo\Actividad;

/**
 * Regla: cada campo, en un día, debería tener un solo código de labor. Si tiene más, se pregunta una vez si es
 * correcto (puede ser un código mal digitado) y lo aceptado queda en campo_actividades_aceptadas.
 */
class CampoActividadReglas
{
    /**
     * Campos que no son parcelas: ahí se mezclan muchos códigos todos los días (vigilancia, cocinera, tractorista…)
     * y preguntar no ayuda. En minúsculas.
     */
    public const CAMPOS_SIN_REVISION = ['fdm'];

    public static function seRevisa(string $campo): bool
    {
        return !in_array(mb_strtolower(trim($campo)), self::CAMPOS_SIN_REVISION, true);
    }
}
