<?php

namespace App\Services\Planilla\RegistroDiario;

/** Reglas sin base de datos del registro diario de planilla (tabla de actividades por tramos). */
class PlanillaRegistroDiarioReglas
{
    /** Campos de un tramo N en cada fila: campo_N, labor_N, entrada_N, salida_N. */
    public const PARTES_TRAMO = ['campo', 'labor', 'entrada', 'salida'];

    /**
     * Códigos con suspensión para los que NO se pregunta "¿hasta cuándo?" al guardar: la falta se registra día a día
     * (no se programa). Vacaciones, DM y licencias sí tienen un fin conocido.
     */
    public const SIN_PREGUNTA_HASTA = ['F'];

    /**
     * Mayor número de tramo que tiene algún dato en las filas. Sirve para no mostrar ni guardar menos columnas de las
     * que hay: con menos, los tramos de más quedaban ocultos y se borraban al guardar la fila.
     */
    public static function tramosUsados(array $filas): int
    {
        $max = 0;
        foreach ($filas as $fila) {
            foreach ((array) $fila as $clave => $valor) {
                if ($valor === null || $valor === '' || !preg_match('/^(campo|labor|entrada|salida)_(\d+)$/', (string) $clave, $m)) {
                    continue;
                }
                $max = max($max, (int) $m[2]);
            }
        }

        return $max;
    }

    /** Columnas a mostrar / guardar: lo guardado en el día, sin bajar de los tramos con datos (mínimo 1). */
    public static function totalActividades(?int $guardado, array $filas): int
    {
        return max(1, (int) $guardado, self::tramosUsados($filas));
    }
}
