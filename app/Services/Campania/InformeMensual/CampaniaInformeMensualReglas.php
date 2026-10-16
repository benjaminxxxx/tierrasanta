<?php

namespace App\Services\Campania\InformeMensual;

use App\Models\CampoCampania;
use Carbon\Carbon;

/**
 * Informe mensual de campaña: reglas de qué campañas entran en un mes, hasta qué fecha (corte) y en qué etapa cae
 * cada fecha.
 *
 * - Cada mes, cada campaña activa ese mes tiene un informe acumulado desde su inicio hasta el corte.
 * - Corte = último día del mes; si la campaña termina ese mes, su fecha de fin (informe de cierre). En ese mismo mes
 *   el campo puede tener también el primer informe de la campaña siguiente.
 * - Etapas (para costos, riego y fertilización): 1) inicio → día antes de la infestación; 2) infestación → día antes
 *   de la reinfestación; 3) reinfestación → fin. Así lo reparte el Excel histórico: los jornales de infestación caen
 *   en la 2ª etapa y los de re-infestación en la 3ª.
 */
class CampaniaInformeMensualReglas
{
    public const ETAPAS = [
        1 => 'Inicio → Infestación',
        2 => 'Infestación → Reinfestación',
        3 => 'Reinfestación → Cosecha',
    ];

    /** Descarga por hectárea por hora (m³/ha/h) si la campaña no tiene una registrada (valor del Excel histórico). */
    public const DESCARGA_RIEGO_DEFECTO = 25.0;

    /** Categorías de productos de almacén que van en el bloque de sanidad (todo lo que no es fertilizante). */
    public const CATEGORIAS_SANIDAD = ['pesticida', 'coadyuvante', 'bioestimulante', 'biologico'];

    /** Nutrientes en el orden del reporte (códigos de producto_nutrientes). */
    public const NUTRIENTES = ['n' => 'N', 'p' => 'P', 'k' => 'K', 'ca' => 'Ca', 'mg' => 'Mg', 'zn' => 'Zn', 'mn' => 'Mn', 'fe' => 'Fe'];

    /**
     * Grupos de orígenes de la BDD de costos que se revisan mes a mes para avisar qué falta registrar.
     * clave => [nombre, orígenes]
     */
    public const COBERTURA_COSTOS = [
        'mano_obra' => ['Mano de obra (planilla y cuadrilla)', ['planilla', 'cuadrilla']],
        'insumos' => ['Fertilizantes y pesticidas', ['fertilizante', 'pesticida']],
        'indirectos' => ['Costo operativo y costo fijo (/costos/mensual)', ['costo_operativo', 'costo_fijo']],
    ];

    public static function inicioMes(int $anio, int $mes): Carbon
    {
        return Carbon::create($anio, $mes, 1)->startOfDay();
    }

    public static function finMes(int $anio, int $mes): Carbon
    {
        return self::inicioMes($anio, $mes)->endOfMonth()->startOfDay();
    }

    /** ¿La campaña estuvo activa algún día del mes? */
    public static function activaEnMes(CampoCampania $campania, int $anio, int $mes): bool
    {
        $inicio = Carbon::parse($campania->fecha_inicio);
        $fin = $campania->fecha_fin ? Carbon::parse($campania->fecha_fin) : null;
        return $inicio->lte(self::finMes($anio, $mes)) && (!$fin || $fin->gte(self::inicioMes($anio, $mes)));
    }

    /** Fecha de corte del informe del mes: fin de mes, o la fecha de fin de la campaña si termina ese mes. */
    public static function fechaCorte(CampoCampania $campania, int $anio, int $mes): Carbon
    {
        $finMes = self::finMes($anio, $mes);
        $fin = $campania->fecha_fin ? Carbon::parse($campania->fecha_fin)->startOfDay() : null;
        return $fin && $fin->lte($finMes) ? $fin : $finMes;
    }

    public static function esCierre(CampoCampania $campania, int $anio, int $mes): bool
    {
        return $campania->fecha_fin && Carbon::parse($campania->fecha_fin)->lte(self::finMes($anio, $mes));
    }

    /**
     * Meses (AAAA-MM) que le corresponden a la campaña, desde su inicio hasta su fin o hasta $hasta (por defecto
     * el mes actual).
     *
     * @return array<int, array{anio:int, mes:int, periodo:string}>
     */
    public static function mesesDeCampania(CampoCampania $campania, ?Carbon $hasta = null): array
    {
        $hasta ??= now();
        $desde = Carbon::parse($campania->fecha_inicio)->startOfMonth();
        $limite = $campania->fecha_fin ? Carbon::parse($campania->fecha_fin) : $hasta;
        if ($limite->gt($hasta)) {
            $limite = $hasta;
        }
        $meses = [];
        for ($m = $desde->copy(); $m->lte($limite); $m->addMonthNoOverflow()) {
            $meses[] = ['anio' => $m->year, 'mes' => $m->month, 'periodo' => $m->format('Y-m')];
        }
        return $meses;
    }

    /**
     * Rangos de cada etapa hasta el corte. Una etapa que todavía no empezó queda en null.
     *
     * @return array<int, ?array{desde:string, hasta:string, en_curso:bool}>
     */
    public static function rangosEtapas(string $inicio, ?string $infestacion, ?string $reinfestacion, string $corte): array
    {
        $infestacion = $infestacion && $infestacion <= $corte ? $infestacion : null;
        $reinfestacion = $infestacion && $reinfestacion && $reinfestacion <= $corte && $reinfestacion >= $infestacion ? $reinfestacion : null;
        $diaAntes = fn(string $f) => Carbon::parse($f)->subDay()->toDateString();

        $rangos = [1 => null, 2 => null, 3 => null];
        $rangos[1] = ['desde' => $inicio, 'hasta' => $infestacion ? $diaAntes($infestacion) : $corte, 'en_curso' => !$infestacion];
        if ($infestacion) {
            $rangos[2] = ['desde' => $infestacion, 'hasta' => $reinfestacion ? $diaAntes($reinfestacion) : $corte, 'en_curso' => !$reinfestacion];
        }
        if ($reinfestacion) {
            $rangos[3] = ['desde' => $reinfestacion, 'hasta' => $corte, 'en_curso' => true];
        }
        return $rangos;
    }

    /** Etapa (1, 2 o 3) de una fecha según los rangos. */
    public static function etapaDeFecha(array $rangos, string $fecha): ?int
    {
        foreach ($rangos as $etapa => $r) {
            if ($r && $fecha >= $r['desde'] && $fecha <= $r['hasta']) {
                return $etapa;
            }
        }
        return null;
    }

    /**
     * Junta meses consecutivos en rangos legibles: ['2025-05','2025-06','2025-08'] → "05/2025 a 06/2025, 08/2025".
     *
     * @param string[] $periodos AAAA-MM ordenados
     */
    public static function rangosDeMeses(array $periodos): string
    {
        $fmt = fn(string $p) => substr($p, 5, 2) . '/' . substr($p, 0, 4);
        $partes = [];
        $inicio = $anterior = null;
        foreach ($periodos as $p) {
            if ($anterior && Carbon::parse("{$anterior}-01")->addMonthNoOverflow()->format('Y-m') === $p) {
                $anterior = $p;
                continue;
            }
            if ($inicio) {
                $partes[] = $inicio === $anterior ? $fmt($inicio) : $fmt($inicio) . ' a ' . $fmt($anterior);
            }
            $inicio = $anterior = $p;
        }
        if ($inicio) {
            $partes[] = $inicio === $anterior ? $fmt($inicio) : $fmt($inicio) . ' a ' . $fmt($anterior);
        }
        return implode(', ', $partes);
    }
}
