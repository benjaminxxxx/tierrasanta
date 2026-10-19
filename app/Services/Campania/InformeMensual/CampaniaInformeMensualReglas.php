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

    // ------------------------------------------------------------------ duración proyectada y avance vegetativo

    /** Meses proyectados por etapa (Excel histórico: 1ª = meses hasta la infestación o 6 si no hay; 2ª y 3ª = 4). */
    public const CONFIG_MESES_ETAPAS = 'campania_meses_proyectados_etapas';
    public const MESES_ETAPAS = [1 => 6, 2 => 4, 3 => 4];

    /** @return array{1:int,2:int,3:int} */
    public static function mesesProyectadosEtapas(): array
    {
        $valor = json_decode((string) \App\Models\Configuracion::find(self::CONFIG_MESES_ETAPAS)?->valor, true);
        $meses = self::MESES_ETAPAS;
        foreach ([1, 2, 3] as $e) {
            if (isset($valor[$e]) && (int) $valor[$e] > 0) {
                $meses[$e] = (int) $valor[$e];
            }
        }
        return $meses;
    }

    public static function guardarMesesProyectadosEtapas(array $meses): void
    {
        $limpios = [];
        foreach ([1, 2, 3] as $e) {
            $n = (int) ($meses[$e] ?? 0);
            if ($n < 1 || $n > 36) {
                throw \Illuminate\Validation\ValidationException::withMessages(['meses_etapas' => "Los meses de la etapa {$e} deben estar entre 1 y 36."]);
            }
            $limpios[$e] = $n;
        }
        \App\Models\Configuracion::updateOrCreate(['codigo' => self::CONFIG_MESES_ETAPAS], [
            'valor' => json_encode($limpios),
            'descripcion' => 'Meses proyectados por etapa de campaña (1ª sin infestación registrada, 2ª, 3ª)',
        ]);
    }

    /**
     * Avance del periodo vegetativo, como el Excel histórico:
     * - 1ª etapa proyectada = meses completos del inicio a la infestación (o el valor configurado si aún no hay);
     *   2ª y 3ª = configuradas. Duración en semanas = meses / 12 × 52.
     * - Avance % = ((corte − inicio) en días / 7) / semanas proyectadas × 100.
     * - Edad acumulada en meses = meses completos + días restantes / días del mes del corte, repartida por etapa.
     */
    public static function avanceVegetativo(string $inicio, ?string $infestacion, string $corte): array
    {
        $proyectados = self::mesesProyectadosEtapas();
        $desde = Carbon::parse($inicio);
        $hasta = Carbon::parse($corte);
        $etapa1Real = $infestacion !== null;
        if ($etapa1Real) {
            $proyectados[1] = (int) $desde->diff(Carbon::parse($infestacion))->m + 12 * $desde->diff(Carbon::parse($infestacion))->y;
        }
        $totalMeses = array_sum($proyectados);
        $semanas = $totalMeses / 12 * 52;
        $dias = $desde->diffInDays($hasta);

        $intervalo = $desde->diff($hasta);
        $mesesCompletos = $intervalo->y * 12 + $intervalo->m;
        $acumulado = $mesesCompletos + $intervalo->d / $hasta->daysInMonth;
        $acum = [1 => min($acumulado, $proyectados[1])];
        $acum[2] = $acumulado > $proyectados[1] ? min($acumulado - $proyectados[1], $proyectados[2]) : 0.0;
        $acum[3] = $acumulado > $proyectados[1] + $proyectados[2] ? $acumulado - $proyectados[1] - $proyectados[2] : 0.0;

        return [
            'meses_proyectados' => $proyectados,
            'etapa1_real' => $etapa1Real,
            'meses_proyectados_total' => $totalMeses,
            'semanas_proyectadas' => round($semanas, 2),
            'dias_transcurridos' => $dias,
            'semanas_transcurridas' => round($dias / 7, 2),
            'avance_pct' => $semanas > 0 ? round($dias / 7 / $semanas * 100, 2) : null,
            'meses_acumulados' => round($acumulado, 2),
            'meses_acumulados_etapa' => array_map(fn($v) => round($v, 2), $acum),
        ];
    }

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
