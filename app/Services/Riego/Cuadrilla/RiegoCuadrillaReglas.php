<?php

namespace App\Services\Riego\Cuadrilla;

use Illuminate\Support\Carbon;

/**
 * Riego hecho por cuadrilleros (sin base de datos).
 *
 * Regla de la empresa: cuando un cuadrillero riega, en su registro diario de cuadrilla ese tiempo va como
 * FDM / labor 81 (Riego y fertilización). El detalle por campo lo da el reporte de riego; la BDD de costos lo
 * costea desde ahí. Otro tramo en el horario del riego (otra labor u otro campo) es el mismo tiempo dos veces.
 */
class RiegoCuadrillaReglas
{
    public const CAMPO = 'FDM';
    public const LABOR = '81';
    /** Diferencia de minutos que se tolera por redondeo */
    public const TOLERANCIA_MIN = 5;

    public static function esTramoRiego(?string $campo, $labor): bool
    {
        return mb_strtoupper(trim((string) $campo)) === self::CAMPO && (string) $labor === self::LABOR;
    }

    /** H:i de una hora (acepta H:i:s). */
    public static function hora(?string $h): ?string
    {
        return $h ? substr(Carbon::parse($h)->format('H:i'), 0, 5) : null;
    }

    /** Minutos en que se cruzan dos rangos de horas (0 si no se cruzan). */
    public static function minutosCruce(?string $a1, ?string $a2, ?string $b1, ?string $b2): int
    {
        [$a1, $a2, $b1, $b2] = array_map([self::class, 'hora'], [$a1, $a2, $b1, $b2]);
        if (!$a1 || !$a2 || !$b1 || !$b2) {
            return 0;
        }
        $ini = max($a1, $b1);
        $fin = min($a2, $b2);
        return $ini < $fin ? (int) Carbon::parse($ini)->diffInMinutes(Carbon::parse($fin)) : 0;
    }

    public static function minutos(?string $inicio, ?string $fin): int
    {
        return $inicio && $fin ? max(0, (int) Carbon::parse(self::hora($inicio))->diffInMinutes(Carbon::parse(self::hora($fin)), false)) : 0;
    }

    /**
     * Tramos de un registro de cuadrilla que no cumplen la regla: se cruzan con el horario del riego y no son FDM/81.
     *
     * @param iterable $tramos objetos o arrays con campo_nombre, codigo_labor, hora_inicio, hora_fin
     * @return array<int, string> textos "D4/67 07:00-09:30"
     */
    public static function tramosFueraDeRegla(iterable $tramos, ?string $riegoInicio, ?string $riegoFin): array
    {
        $malos = [];
        foreach ($tramos as $t) {
            $t = (object) $t;
            if (self::esTramoRiego($t->campo_nombre ?? null, $t->codigo_labor ?? null)) {
                continue;
            }
            if (self::minutosCruce($t->hora_inicio ?? null, $t->hora_fin ?? null, $riegoInicio, $riegoFin) > 0) {
                $malos[] = "{$t->campo_nombre}/{$t->codigo_labor} " . self::hora($t->hora_inicio) . '-' . self::hora($t->hora_fin);
            }
        }
        return $malos;
    }
}
