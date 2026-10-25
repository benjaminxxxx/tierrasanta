<?php

namespace App\Services\Planilla\Laboral;

use Illuminate\Support\Carbon;

/**
 * Reglas del historial laboral (sin base de datos: reciben listas y devuelven mensajes). Las usan el Excel de empleados,
 * los formularios y PlanillaLaboralProceso, así todos validan igual.
 *
 * Fechas en texto Y-m-d. Un contrato: ['ref' => clave, 'inicio' => ..., 'fin' => ?..., 'fin_prueba' => ?...].
 * Una partícula: ['ref' => clave, 'desde' => ...].
 */
class PlanillaLaboralReglas
{
    public const TIPOS_CONTRATO = ['plazo fijo', 'indefinido', 'temporal'];
    public const TIPOS_PLANILLA = ['agraria', 'oficina', 'externa'];
    public const TIPOS_INGRESO = ['planilla', 'honorarios'];
    public const MODALIDADES_PAGO = ['mensual', 'quincenal', 'semanal'];

    public static function texto(?string $fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : 'sin fin';
    }

    public static function periodo(array $c): string
    {
        return self::texto($c['inicio']) . '–' . self::texto($c['fin'] ?? null);
    }

    /**
     * Contratos de UN empleado: fin antes del inicio, periodo de prueba fuera del contrato y cruces entre contratos.
     * Puede haber días sin contrato entre uno y otro.
     * @return array<string, string[]> ref => errores
     */
    public static function erroresContratos(array $contratos): array
    {
        $e = [];
        foreach ($contratos as $c) {
            if ($c['fin'] !== null && $c['fin'] < $c['inicio']) {
                $e[$c['ref']][] = 'El fin del contrato (' . self::texto($c['fin']) . ') es anterior a su inicio (' . self::texto($c['inicio']) . ').';
            }
            if (!empty($c['fin_prueba']) && ($c['fin_prueba'] < $c['inicio'] || ($c['fin'] !== null && $c['fin_prueba'] > $c['fin']))) {
                $e[$c['ref']][] = 'El fin del periodo de prueba (' . self::texto($c['fin_prueba']) . ') debe caer dentro del contrato.';
            }
        }
        $ordenados = array_values($contratos);
        usort($ordenados, fn($a, $b) => strcmp($a['inicio'], $b['inicio']));
        for ($i = 0; $i < count($ordenados); $i++) {
            for ($j = $i + 1; $j < count($ordenados); $j++) {
                $a = $ordenados[$i];
                $b = $ordenados[$j];
                if (self::seCruzan($a, $b)) {
                    $msg = 'Se cruza con otro contrato del mismo empleado: ' . self::periodo($a) . ' y ' . self::periodo($b) . '.';
                    $e[$a['ref']][] = $msg;
                    $e[$b['ref']][] = $msg;
                }
            }
        }
        return array_map('array_values', array_map('array_unique', $e));
    }

    public static function seCruzan(array $a, array $b): bool
    {
        $finA = $a['fin'] ?? '9999-12-31';
        $finB = $b['fin'] ?? '9999-12-31';
        return $a['inicio'] <= $finB && $b['inicio'] <= $finA;
    }

    /** Contrato que contiene la fecha (o null). */
    public static function contratoQueCubre(array $contratos, string $fecha): ?array
    {
        foreach ($contratos as $c) {
            if ($c['inicio'] <= $fecha && ($c['fin'] === null || $c['fin'] >= $fecha)) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Partículas de contrato (asignaciones, remuneraciones) de UN empleado: cada "desde" debe caer dentro de un contrato
     * y no repetirse en el mismo contrato.
     * @param string $titulo "La remuneración", "La asignación"…
     * @return array<string, string[]> ref => errores
     */
    public static function erroresParticulasDeContrato(array $particulas, array $contratos, string $titulo): array
    {
        $e = [];
        $vistos = [];
        foreach ($particulas as $p) {
            $c = self::contratoQueCubre($contratos, $p['desde']);
            if (!$c) {
                $e[$p['ref']][] = "{$titulo} desde " . self::texto($p['desde']) . ' no cae dentro de ningún contrato del empleado'
                    . ($contratos ? ' (' . implode(', ', array_map([self::class, 'periodo'], $contratos)) . ').' : ' (no tiene contratos).');
                continue;
            }
            $k = $c['ref'] . '|' . $p['desde'];
            if (isset($vistos[$k])) {
                $e[$p['ref']][] = "{$titulo} desde " . self::texto($p['desde']) . ' está repetida en el mismo contrato.';
            }
            $vistos[$k] = true;
        }
        return $e;
    }

    /**
     * Contratos sin partícula en su fecha de inicio (la planilla no sabría su sueldo o grupo los primeros días).
     * @return array<string, string> ref del contrato => mensaje
     */
    public static function contratosSinInicio(array $contratos, array $particulas, string $titulo): array
    {
        $r = [];
        foreach ($contratos as $c) {
            $tiene = false;
            foreach ($particulas as $p) {
                if ($p['desde'] === $c['inicio']) {
                    $tiene = true;
                    break;
                }
            }
            if (!$tiene) {
                $r[$c['ref']] = "El contrato " . self::periodo($c) . " no tiene {$titulo} desde su inicio (" . self::texto($c['inicio']) . ').';
            }
        }
        return $r;
    }

    /** Partículas del empleado (pensión, pago): sin "desde" repetido. @return array<string, string[]> */
    public static function erroresParticulasDeEmpleado(array $particulas, string $titulo): array
    {
        $e = [];
        $vistos = [];
        foreach ($particulas as $p) {
            if (isset($vistos[$p['desde']])) {
                $e[$p['ref']][] = "{$titulo} desde " . self::texto($p['desde']) . ' está repetida.';
            }
            $vistos[$p['desde']] = true;
        }
        return $e;
    }
}
