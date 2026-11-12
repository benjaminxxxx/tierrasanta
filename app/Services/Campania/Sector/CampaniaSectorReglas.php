<?php

namespace App\Services\Campania\Sector;

/**
 * Mapa de etapas: cada campaña tiene uno o más grupos de hileras (sectores) y cada grupo pasa por las etapas del
 * ciclo en sus propias fechas (las hileras de un campo pueden ir desfasadas: unas se siembran o infestan antes).
 *
 * La etapa de una labor sale de su mano de obra. Las labores que no son de una etapa (culturales, sanidad, riego,
 * limpieza, FDM…) se hacen en cualquier momento y no cuentan.
 */
class CampaniaSectorReglas
{
    /** etapa => [nombre, color, familia]. La familia agrupa etapas que conviven (re-infestación y cosecha de madres son parte de la infestación). */
    public const ETAPAS = [
        'preparacion' => ['Preparación de terreno', '#78716C', 'preparacion'],
        'siembra' => ['Siembra', '#16A34A', 'siembra'],
        'infestacion' => ['Infestación', '#DC2626', 'infestacion'],
        'reinfestacion' => ['Reinfestación', '#F97316', 'infestacion'],
        'cosecha_madres' => ['Cosecha de madres', '#DB2777', 'infestacion'],
        'cosecha' => ['Cosecha (poda)', '#CA8A04', 'cosecha'],
    ];

    public const SIN_ETAPA = ['Sin etapa registrada', '#CBD5E1'];

    public const FAMILIAS = [
        'preparacion' => 'preparación de terreno',
        'siembra' => 'siembra',
        'infestacion' => 'infestación',
        'cosecha' => 'cosecha (poda)',
    ];

    /** mano de obra de la labor => etapa */
    public const ETAPA_POR_MANO_OBRA = [
        'preparacion_terreno' => 'preparacion',
        'siembra' => 'siembra',
        'infestacion' => 'infestacion',
        'reinfestacion' => 'reinfestacion',
        'precosecha' => 'cosecha_madres',
        'cosecha' => 'cosecha',
    ];

    /** Días sin colocar infestadores o sin podar para considerar que empezó otro grupo de hileras (detección). */
    public const DIAS_GRUPO = 7;

    /** Más grupos que esto en la detección se toma como ruido: se deja este máximo y se revisa a mano. */
    public const MAX_GRUPOS = 6;

    /** Orden del ciclo por familia: un grupo de hileras solo avanza a la familia siguiente (no se salta ni retrocede). */
    public const ORDEN_FAMILIAS = ['preparacion', 'siembra', 'infestacion', 'cosecha'];

    /** Familias con las que puede empezar un grupo que aún no tiene etapa (la poda necesita una infestación antes). */
    public const FAMILIAS_INICIALES = ['preparacion', 'siembra', 'infestacion'];

    /**
     * ¿Un grupo de hileras en $actual (null = sin etapa) puede pasar a $etapa al registrarse una labor de ella?
     * Ej.: siembra → infestación sí; preparación → infestación no (falta la siembra); infestación → siembra no.
     */
    public static function puedeAvanzar(?string $actual, string $etapa): bool
    {
        $nueva = self::familia($etapa);
        if ($actual === null) {
            return in_array($nueva, self::FAMILIAS_INICIALES, true);
        }
        $i = array_search(self::familia($actual), self::ORDEN_FAMILIAS, true);
        return $i !== false && (self::ORDEN_FAMILIAS[$i + 1] ?? null) === $nueva;
    }

    public static function etapaDeManoObra(?string $manoObra): ?string
    {
        return self::ETAPA_POR_MANO_OBRA[(string) $manoObra] ?? null;
    }

    public static function familia(string $etapa): ?string
    {
        return self::ETAPAS[$etapa][2] ?? null;
    }

    public static function nombre(?string $etapa): string
    {
        return $etapa ? (self::ETAPAS[$etapa][0] ?? $etapa) : self::SIN_ETAPA[0];
    }

    public static function color(?string $etapa): string
    {
        return $etapa ? (self::ETAPAS[$etapa][1] ?? self::SIN_ETAPA[1]) : self::SIN_ETAPA[1];
    }

    /**
     * Labores que EMPIEZAN un grupo de hileras al detectar: colocar infestadores (infestación) y podar (cosecha).
     * Las demás de esas etapas (retiro de malla, recojo, vaciado, traslado) siguen semanas después y no son otro grupo.
     */
    public static function iniciaGrupo(string $etapa, string $nombreLabor): bool
    {
        $n = mb_strtolower($nombreLabor);
        return match ($etapa) {
            'infestacion' => str_contains($n, 'colocaci') && str_contains($n, 'infestador'),
            'cosecha' => str_starts_with($n, 'poda'),
            default => false,
        };
    }

    /**
     * Reparte las hileras 1..$total en $grupos tramos seguidos lo más parejos posible.
     *
     * @return array<int, array{0:int, 1:int}> [[desde, hasta], …]
     */
    public static function repartirHileras(int $total, int $grupos): array
    {
        $grupos = max(1, min($grupos, $total));
        $rangos = [];
        $desde = 1;
        for ($i = 0; $i < $grupos; $i++) {
            $tamanio = intdiv($total, $grupos) + ($i < $total % $grupos ? 1 : 0);
            $rangos[] = [$desde, $desde + $tamanio - 1];
            $desde += $tamanio;
        }
        return $rangos;
    }
}
