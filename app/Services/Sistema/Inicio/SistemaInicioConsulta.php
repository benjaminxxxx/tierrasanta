<?php

namespace App\Services\Sistema\Inicio;

use App\Models\TareaPendiente;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Datos del inicio (dashboard) para un mes: indicadores con su variación contra el mes anterior, series del año
 * (cosecha de cochinilla y costo de mano de obra por mes) y alertas (tareas pendientes del mes).
 * Los costos salen de resumen_costo_diarios (la BDD de costos es la fuente única).
 */
class SistemaInicioConsulta
{
    /** Tipos de resumen_costo_diarios que son mano de obra, agrupados como se muestran. */
    public const GRUPOS_MANO_OBRA = [
        'Planilla' => ['planilla', 'planilla_bono_productividad'],
        'Cuadrilla' => ['cuadrilla', 'cuadrilla_bono'],
        'Indirecta' => ['mano_obra_indirecta'],
    ];

    private const MINUTOS_CACHE = 5;

    public function resumen(int $anio, int $mes): array
    {
        return Cache::remember($this->claveCache($anio, $mes), now()->addMinutes(self::MINUTOS_CACHE), fn() => [
            'indicadores' => $this->indicadores($anio, $mes),
            'cosecha' => $this->cosechaPorMes($anio),
            'mano_obra' => $this->manoObraPorMes($anio),
            'generado' => now()->format('H:i'),
        ]);
    }

    public function olvidar(int $anio, int $mes): void
    {
        Cache::forget($this->claveCache($anio, $mes));
    }

    private function claveCache(int $anio, int $mes): string
    {
        return "sistema.inicio.{$anio}-{$mes}";
    }

    private function indicadores(int $anio, int $mes): array
    {
        $previo = Carbon::create($anio, $mes, 1)->subMonth();
        $par = fn(callable $f) => ['valor' => $f($anio, $mes), 'anterior' => $f($previo->year, $previo->month)];

        return [
            'cosecha_kg' => $par(fn($a, $m) => (float) DB::table('cochinilla_ingresos')->whereYear('fecha', $a)->whereMonth('fecha', $m)->sum('total_kilos')),
            'cosecha_anio_kg' => (float) DB::table('cochinilla_ingresos')->whereYear('fecha', $anio)
                ->whereMonth('fecha', '<=', $mes)->sum('total_kilos'),
            'mano_obra' => $par(fn($a, $m) => (float) DB::table('resumen_costo_diarios')->whereYear('fecha', $a)->whereMonth('fecha', $m)
                ->whereIn('origen_tipo', array_merge(...array_values(self::GRUPOS_MANO_OBRA)))->sum('costo_total')),
            'compras' => $par(fn($a, $m) => (float) DB::table('compras')->whereNull('deleted_at')->whereYear('fecha_emision', $a)->whereMonth('fecha_emision', $m)
                ->sum(DB::raw("CASE WHEN moneda = 'USD' THEN total * COALESCE(tipo_cambio, 1) ELSE total END"))),
            'compras_cantidad' => DB::table('compras')->whereNull('deleted_at')->whereYear('fecha_emision', $anio)->whereMonth('fecha_emision', $mes)->count(),
            'planilla_agraria' => PlanillaEmpleadoServicio::totalActivos($mes, $anio, 'agraria'),
            'planilla_oficina' => PlanillaEmpleadoServicio::totalActivos($mes, $anio, 'oficina'),
            'cuadrilleros' => $par(fn($a, $m) => DB::table('cuad_registros_diarios')->whereYear('fecha', $a)->whereMonth('fecha', $m)
                ->where('total_horas', '>', 0)->distinct()->count('cuadrillero_id')),
            'campanias_en_curso' => $this->campaniasEnCurso($anio, $mes),
            'infestacion' => (array) DB::table('cochinilla_infestaciones')->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
                ->selectRaw('COALESCE(SUM(kg_madres), 0) kg, COUNT(DISTINCT campo_nombre) campos')->first(),
        ];
    }

    /** Campañas abiertas al cierre del mes (o hoy, si es el mes en curso). */
    private function campaniasEnCurso(int $anio, int $mes): int
    {
        $corte = min(Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString(), Carbon::today()->toDateString());
        return DB::table('campos_campanias')->where('fecha_inicio', '<=', $corte)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $corte))->count();
    }

    /** @return array<int, float> mes (1-12) => kilos */
    private function cosechaPorMes(int $anio): array
    {
        $kg = DB::table('cochinilla_ingresos')->whereYear('fecha', $anio)->selectRaw('MONTH(fecha) m, SUM(total_kilos) kg')
            ->groupBy('m')->pluck('kg', 'm');
        return array_map(fn($m) => round((float) ($kg[$m] ?? 0), 2), array_combine(range(1, 12), range(1, 12)));
    }

    /** @return array<string, array<int, float>> grupo => mes (1-12) => soles */
    private function manoObraPorMes(int $anio): array
    {
        $filas = DB::table('resumen_costo_diarios')->whereYear('fecha', $anio)
            ->whereIn('origen_tipo', array_merge(...array_values(self::GRUPOS_MANO_OBRA)))
            ->selectRaw('MONTH(fecha) m, origen_tipo, SUM(costo_total) total')->groupBy('m', 'origen_tipo')->get();
        $r = [];
        foreach (self::GRUPOS_MANO_OBRA as $grupo => $tipos) {
            $r[$grupo] = array_fill(1, 12, 0.0);
            foreach ($filas->whereIn('origen_tipo', $tipos) as $f) {
                $r[$grupo][(int) $f->m] += round((float) $f->total, 2);
            }
        }
        return $r;
    }

    /**
     * Tareas pendientes del mes (sin caché: cambian al ejecutarlas). Las de tipo "danger" primero.
     *
     * @return array{total: int, tareas: array<int, array>}
     */
    public function alertas(int $anio, int $mes, int $limite = 6): array
    {
        $inicio = Carbon::create($anio, $mes, 1);
        $tareas = TareaPendiente::where('estado', 'pendiente')->whereNull('parent_id')
            ->enPeriodo($inicio->toDateString(), $inicio->copy()->endOfMonth()->toDateString())
            ->get(['id', 'titulo', 'descripcion', 'variante', 'cantidad_afectados'])
            ->sortBy([fn($a, $b) => ($a->variante === 'danger' ? 0 : 1) <=> ($b->variante === 'danger' ? 0 : 1), ['cantidad_afectados', 'desc']])
            ->values();
        return [
            'total' => $tareas->count(),
            'tareas' => $tareas->take($limite)->map(fn($t) => [
                'titulo' => $t->titulo, 'descripcion' => $t->descripcion, 'variante' => $t->variante, 'cantidad' => (int) $t->cantidad_afectados,
            ])->all(),
        ];
    }
}
