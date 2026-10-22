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

    /** Rol de una labor en la campaña (labores.rol_campania). null = labor normal. */
    public const ROL_COSECHA = 'cosecha';
    public const ROL_NEUTRA = 'neutra';
    public const ROLES = [
        self::ROL_COSECHA => 'Cosecha (marca el fin de la campaña)',
        self::ROL_NEUTRA => 'Neutra (no indica campaña: administración, vigilancia, maquinaria…)',
    ];

    /**
     * Poda, poda mamá, poda barrido, cosecha mamá / pre-cosecha y traslados de cosecha.
     * NO incluye 69 "Post-cosecha (venteado, extendido y filtrado)": es lo que se hace después de cosechar (procesar la
     * cochinilla, limpiar el campo…) y según la regla del ingeniero ya es de la campaña siguiente. Contarla como
     * cosecha hacía ver "cosechada" una campaña recién iniciada (ej. campo 1 T.2026: post-cosecha al vaciar mallitas).
     */
    private const LABORES_COSECHA = [41, 42, 52, 67, 68, 144, 145, 146];

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

    /** Códigos de las labores marcadas "cosecha" (Labores → Rol en la campaña). @return int[] */
    public static function laboresCosecha(): array
    {
        return self::porRol(self::ROL_COSECHA, self::LABORES_COSECHA);
    }

    /** Códigos de las labores marcadas "neutra". @return int[] */
    public static function laboresNeutras(): array
    {
        return self::porRol(self::ROL_NEUTRA, self::LABORES_NEUTRAS);
    }

    /**
     * El rol lo define cada labor (labores.rol_campania), así una labor de cosecha nueva se marca al crearla.
     * Incluye labores eliminadas (sus registros antiguos siguen contando). Las constantes solo se usan si ninguna
     * labor tiene el rol (antes de la migración).
     *
     * @return int[]
     */
    private static function porRol(string $rol, array $porDefecto): array
    {
        if (!array_key_exists($rol, self::$cache)) {
            $codigos = \App\Models\Labores::withTrashed()->where('rol_campania', $rol)->pluck('codigo')->map(fn($c) => (int) $c)->unique()->values()->all();
            self::$cache[$rol] = $codigos ?: $porDefecto;
        }
        return self::$cache[$rol];
    }

    public static function diasMaximoVenta(bool $esMama): int
    {
        return $esMama ? self::DIAS_MAX_VENTA_MAMA : self::DIAS_MAX_VENTA;
    }
}
