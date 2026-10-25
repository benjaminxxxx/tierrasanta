<?php

namespace App\Services\Planilla\ListaMensual;

/**
 * Orden de la lista mensual del registro diario (sin base de datos).
 *
 * Un mes nuevo copia el orden del último mes con lista: los que siguen contratados mantienen su posición relativa
 * (los que se fueron no dejan huecos, el orden se corre) y los contratados nuevos van al final.
 */
class PlanillaListaMensualReglas
{
    /**
     * @param int[] $empleados ids de los que van en el mes, en el orden en que entran los nuevos (por su "orden" de empleado)
     * @param array<int, int> $ordenAnterior plan_empleado_id => orden del último mes con lista
     * @return array<int, int> plan_empleado_id => orden (1..n)
     */
    public static function ordenar(array $empleados, array $ordenAnterior): array
    {
        $siguen = array_values(array_filter($empleados, fn($id) => isset($ordenAnterior[$id])));
        usort($siguen, fn($a, $b) => $ordenAnterior[$a] <=> $ordenAnterior[$b]);
        $nuevos = array_values(array_filter($empleados, fn($id) => !isset($ordenAnterior[$id])));

        $orden = [];
        foreach (array_merge($siguen, $nuevos) as $i => $id) {
            $orden[$id] = $i + 1;
        }
        return $orden;
    }

    /**
     * Renumera 1..n respetando el orden actual (después de quitar a alguien, para que no queden huecos).
     *
     * @param array<int, int> $orden id del detalle => orden
     * @return array<int, int> id del detalle => orden nuevo
     */
    public static function compactar(array $orden): array
    {
        asort($orden);
        $r = [];
        $n = 1;
        foreach (array_keys($orden) as $id) {
            $r[$id] = $n++;
        }
        return $r;
    }

    /** Se genera sola la lista de un mes solo hasta el mes siguiente al actual (no al navegar a fechas lejanas). */
    public static function sePuedeGenerar(int $mes, int $anio): bool
    {
        return $anio * 12 + $mes <= now()->year * 12 + now()->month + 1;
    }
}
