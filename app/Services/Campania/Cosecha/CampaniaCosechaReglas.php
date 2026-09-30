<?php

namespace App\Services\Campania\Cosecha;

use App\Models\Configuracion;

/**
 * Reglas de negocio de la cosecha de una campaña.
 *
 * - Una campaña termina con la cosecha, que puede durar varios días. Antes de cualquier otra actividad
 *   (preparado de tierra, limpieza, fumigación…) la campaña debe cerrarse: esas labores ya son de la siguiente.
 * - La venta se carga a la campaña que se cosechó (por el ingreso de cochinilla, no por rango de fechas) y debe
 *   hacerse hasta 15 días después de la cosecha; si la cochinilla se usó como mamá, hasta 100 días.
 *
 * Las listas de labores se pueden ajustar sin tocar código en la tabla `configuracion`
 * (códigos de labor separados por coma).
 */
class CampaniaCosechaReglas
{
    public const DIAS_MAX_VENTA = 15;
    public const DIAS_MAX_VENTA_MAMA = 100;

    /** Días sin cosecha para considerar que la cosecha terminó (antes se muestra "cosechando"). */
    public const DIAS_COSECHANDO = 2;

    /** Días desde la última cosecha para recomendar cerrar una campaña abierta aunque no haya otras labores. */
    public const DIAS_SUGERIR_CIERRE = 15;

    public const CONFIG_LABORES_COSECHA = 'campania_labores_cosecha';
    public const CONFIG_LABORES_NEUTRAS = 'campania_labores_neutras';

    /** Poda, poda mamá, poda barrido, cosecha mamá / pre-cosecha, traslados de cosecha y post-cosecha. */
    private const LABORES_COSECHA = [41, 42, 52, 67, 68, 69, 144, 145, 146];

    /**
     * Labores que no indican el inicio de otra campaña (administración, vigilancia, cocina, maquinaria,
     * mantenimiento de infraestructura, naranja…). Todo lo demás después de la cosecha sí lo indica.
     */
    private const LABORES_NEUTRAS = [
        79, 80, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100,
        101, 102, 103, 104, 111, 112, 113, 114, 115, 116, 117, 122, 133, 140, 142, 143, 155, 158,
    ];

    /**
     * Observaciones de ingreso de cochinilla que NO son cosecha: cochinilla recuperada al vaciar los
     * infestadores de una infestación (pertenece a la infestación de la campaña nueva, no a su cosecha).
     */
    private const OBSERVACIONES_NO_COSECHA = ['infestador_carton', 'infestador_malla', 'infestador_tubo', 'infestadores'];

    private static array $cache = [];

    /** @return string[] */
    public static function observacionesNoCosecha(): array
    {
        return self::OBSERVACIONES_NO_COSECHA;
    }

    /** @return int[] */
    public static function laboresCosecha(): array
    {
        return self::lista(self::CONFIG_LABORES_COSECHA, self::LABORES_COSECHA);
    }

    /** @return int[] */
    public static function laboresNeutras(): array
    {
        return self::lista(self::CONFIG_LABORES_NEUTRAS, self::LABORES_NEUTRAS);
    }

    public static function diasMaximoVenta(bool $esMama): int
    {
        return $esMama ? self::DIAS_MAX_VENTA_MAMA : self::DIAS_MAX_VENTA;
    }

    /** @return int[] */
    private static function lista(string $codigo, array $porDefecto): array
    {
        if (!array_key_exists($codigo, self::$cache)) {
            $valor = Configuracion::find($codigo)?->valor;
            $codigos = $valor
                ? array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/', $valor))))
                : [];
            self::$cache[$codigo] = $codigos ?: $porDefecto;
        }
        return self::$cache[$codigo];
    }
}
