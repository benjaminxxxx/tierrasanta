<?php

namespace App\Services\Campo\Produccion;

use App\Models\Actividad;
use App\Models\ProduccionRegistro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lectura de la producción de campo (fuente única de cantidades, con y sin bono): por actividad y trabajador (para
 * bonificaciones), resumen por día / campo / labor y cuadre de lo cosechado contra el ingreso de cochinilla.
 */
class CampoProduccionConsulta
{
    /** Unidad de un registro (p, a = actividad): la guardada o, si está vacía (registros copiados), la de la labor vigente. */
    public const UNIDAD = "COALESCE(NULLIF(p.unidad, ''), (SELECT l.unidades FROM labores l WHERE l.codigo = a.codigo_labor
        AND (l.vigente_desde IS NULL OR l.vigente_desde <= a.fecha) ORDER BY l.vigente_desde DESC LIMIT 1))";

    /** Clave de un trabajador: "c:ID" cuadrillero, "p:ID" empleado de planilla. */
    public static function clave(?int $cuadrilleroId, ?int $empleadoId): ?string
    {
        return $cuadrilleroId ? "c:{$cuadrilleroId}" : ($empleadoId ? "p:{$empleadoId}" : null);
    }

    /**
     * Cantidades con bono de una actividad por trabajador y recojo (lo que muestra bonificaciones).
     * @return array<string, array<int, float>> clave => [recojo => cantidad]
     */
    public static function conBonoPorTrabajador(int $actividadId): array
    {
        $r = [];
        foreach (ProduccionRegistro::where('actividad_id', $actividadId)->where('con_bono', true)->get() as $p) {
            if ($k = self::clave($p->cuadrillero_id, $p->plan_empleado_id)) {
                $r[$k][$p->numero_recojo] = (float) $p->cantidad;
            }
        }
        return $r;
    }

    /**
     * Filas sin bono de una actividad, agrupadas por trabajador (o sin trabajador) para editarlas.
     * @return array<int, array{tipo: ?string, trabajador_id: ?int, nombre: ?string, recojos: array<int, float>, observacion: ?string}>
     */
    public static function sinBono(int $actividadId): array
    {
        $filas = [];
        $registros = ProduccionRegistro::with(['cuadrillero:id,nombres', 'empleado'])->where('actividad_id', $actividadId)
            ->where('con_bono', false)->orderBy('id')->get();
        foreach ($registros as $p) {
            // Sin trabajador: cada registro es su propia fila (no se pueden agrupar)
            $k = self::clave($p->cuadrillero_id, $p->plan_empleado_id) ?? "s:{$p->id}";
            $filas[$k] ??= ['tipo' => $p->tipo_trabajador, 'trabajador_id' => $p->cuadrillero_id ?? $p->plan_empleado_id,
                'nombre' => $p->nombre_trabajador, 'recojos' => [], 'observacion' => $p->observacion];
            $filas[$k]['recojos'][$p->numero_recojo] = (float) $p->cantidad;
        }
        return array_values($filas);
    }

    /** Total con bono de un trabajador en una actividad (lo que usan costos e historial laboral). */
    public static function totalTrabajador(int $actividadId, ?int $cuadrilleroId, ?int $empleadoId): float
    {
        return (float) ProduccionRegistro::where('actividad_id', $actividadId)
            ->when($cuadrilleroId, fn($q) => $q->where('cuadrillero_id', $cuadrilleroId))
            ->when($empleadoId, fn($q) => $q->where('plan_empleado_id', $empleadoId))
            ->sum('cantidad');
    }

    /**
     * Resumen por actividad (día + campo + labor): con bono, sin bono y trabajadores.
     * @return Collection<int, object>
     */
    public static function resumen(string $desde, string $hasta, ?string $campo = null, ?int $codigoLabor = null, ?string $buscar = null): Collection
    {
        return DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
            ->whereBetween('a.fecha', [$desde, $hasta])
            ->when($campo, fn($q) => $q->where('a.campo', $campo))
            ->when($codigoLabor, fn($q) => $q->where('a.codigo_labor', $codigoLabor))
            ->when($buscar, fn($q) => $q->where(fn($q) => $q->where('a.nombre_labor', 'like', "%{$buscar}%")->orWhere('a.campo', 'like', "%{$buscar}%")))
            ->groupBy('a.id', 'a.fecha', 'a.campo', 'a.codigo_labor', 'a.nombre_labor')
            ->orderByDesc('a.fecha')->orderBy('a.campo')->orderBy('a.codigo_labor')
            ->selectRaw('a.id as actividad_id, a.fecha, a.campo, a.codigo_labor, a.nombre_labor, MAX('.self::UNIDAD.') as unidad,
                SUM(CASE WHEN p.con_bono = 1 THEN p.cantidad ELSE 0 END) as con_bono,
                SUM(CASE WHEN p.con_bono = 0 THEN p.cantidad ELSE 0 END) as sin_bono,
                SUM(p.cantidad) as total,
                COUNT(DISTINCT COALESCE(CONCAT("c", p.cuadrillero_id), CONCAT("p", p.plan_empleado_id))) as trabajadores')
            ->get();
    }

    /**
     * Totales del periodo por campo y unidad (mamá / cosecha en kg, lavadores…), con y sin bono.
     * @return Collection<int, object>
     */
    public static function totalesPorCampo(string $desde, string $hasta, ?string $campo = null): Collection
    {
        return DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
            ->whereBetween('a.fecha', [$desde, $hasta])->when($campo, fn($q) => $q->where('a.campo', $campo))
            ->groupBy('a.campo', 'a.codigo_labor', 'a.nombre_labor')
            ->orderBy('a.campo')->orderBy('a.codigo_labor')
            ->selectRaw('a.campo, a.codigo_labor, a.nombre_labor, MAX(' . self::UNIDAD . ') as unidad,
                SUM(CASE WHEN p.con_bono = 1 THEN p.cantidad ELSE 0 END) as con_bono,
                SUM(CASE WHEN p.con_bono = 0 THEN p.cantidad ELSE 0 END) as sin_bono, SUM(p.cantidad) as total')
            ->get();
    }

    /**
     * Cuadre de kilos cosechados en campo (labores de cosecha en KG, con y sin bono) contra el ingreso de cochinilla
     * (poda mamá y poda cosecha), por campo en el periodo y por día. Estado según CampoProduccionReglas::estadoCuadre.
     * @return array{campos: array, dias: array}
     */
    public static function cuadreIngreso(string $desde, string $hasta, ?string $campo = null): array
    {
        $codigosCosecha = DB::table('labores')->where('rol_campania', CampoProduccionReglas::ROL_CUADRE)->pluck('codigo');
        $campoDia = DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
            ->whereBetween('a.fecha', [$desde, $hasta])->when($campo, fn($q) => $q->where('a.campo', $campo))
            ->whereRaw(self::UNIDAD . ' = ?', [CampoProduccionReglas::UNIDAD_CUADRE])->whereIn('a.codigo_labor', $codigosCosecha)
            ->groupBy('a.campo', 'a.fecha')->selectRaw('a.campo, a.fecha, SUM(p.cantidad) as kg')->get();
        $ingresoDia = DB::table('cochinilla_ingresos')->whereBetween('fecha', [$desde, $hasta])->when($campo, fn($q) => $q->where('campo', $campo))
            ->whereIn('observacion', CampoProduccionReglas::INGRESOS_CUADRE)
            ->groupBy('campo', 'fecha')->selectRaw('campo, fecha, SUM(total_kilos) as kg, COUNT(*) as ingresos')->get();

        $dias = [];
        foreach ($campoDia as $r) {
            $dias["{$r->campo}|{$r->fecha}"] = ['campo' => $r->campo, 'fecha' => $r->fecha, 'campo_kg' => (float) $r->kg, 'ingreso_kg' => 0.0];
        }
        foreach ($ingresoDia as $r) {
            $k = "{$r->campo}|{$r->fecha}";
            $dias[$k] ??= ['campo' => $r->campo, 'fecha' => $r->fecha, 'campo_kg' => 0.0, 'ingreso_kg' => 0.0];
            $dias[$k]['ingreso_kg'] = (float) $r->kg;
        }
        $campos = [];
        foreach ($dias as &$d) {
            $d += self::diferencia($d['campo_kg'], $d['ingreso_kg']);
            $c = &$campos[$d['campo']];
            $c ??= ['campo' => $d['campo'], 'campo_kg' => 0.0, 'ingreso_kg' => 0.0, 'dias_con_diferencia' => 0];
            $c['campo_kg'] += $d['campo_kg'];
            $c['ingreso_kg'] += $d['ingreso_kg'];
            $c['dias_con_diferencia'] += $d['estado'] !== 'ok' ? 1 : 0;
            unset($c);
        }
        unset($d);
        foreach ($campos as &$c) {
            $c += self::diferencia($c['campo_kg'], $c['ingreso_kg']);
        }
        unset($c);
        usort($dias, fn($a, $b) => [$b['fecha'], $a['campo']] <=> [$a['fecha'], $b['campo']]);
        ksort($campos, SORT_NATURAL);
        return ['campos' => array_values($campos), 'dias' => array_values($dias)];
    }

    /**
     * Cuadre de infestación por día y campo: lo que registró el módulo de infestación (kg de mamá que salieron y envases
     * = lavadores) contra lo que registró el campo en sus labores ese día y en ese campo (llenada, colocación… con y sin
     * bono). Sin semáforo: se muestra la diferencia de cada labor contra los envases y los kg de mamá por envase.
     * @return array{dias: array, labores: array}
     */
    public static function cuadreInfestacion(string $desde, string $hasta, ?string $campo = null): array
    {
        $infestaciones = DB::table('cochinilla_infestaciones')->whereBetween('fecha', [$desde, $hasta])
            ->when($campo, fn($q) => $q->where('campo_nombre', $campo))
            ->groupBy('fecha', 'campo_nombre')
            ->selectRaw('fecha, campo_nombre as campo, SUM(kg_madres) as kg_madres, SUM(numero_envases) as envases,
                GROUP_CONCAT(DISTINCT campo_origen_nombre ORDER BY campo_origen_nombre SEPARATOR ", ") as origenes,
                GROUP_CONCAT(DISTINCT tipo_infestacion SEPARATOR ", ") as tipos')
            ->get();
        if ($infestaciones->isEmpty()) {
            return ['dias' => [], 'labores' => []];
        }
        $produccion = DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
            ->whereBetween('a.fecha', [$desde, $hasta])->whereIn('a.campo', $infestaciones->pluck('campo')->unique())
            ->groupBy('a.fecha', 'a.campo', 'a.codigo_labor', 'a.nombre_labor')
            ->selectRaw('a.fecha, a.campo, a.codigo_labor, a.nombre_labor, MAX(' . self::UNIDAD . ') as unidad, SUM(p.cantidad) as cantidad')
            ->get()->groupBy(fn($r) => Carbon::parse($r->fecha)->toDateString() . '|' . $r->campo);

        $dias = [];
        $labores = [];
        foreach ($infestaciones as $i) {
            $fecha = Carbon::parse($i->fecha)->toDateString();
            $envases = (float) $i->envases;
            $enCampo = [];
            foreach ($produccion->get("{$fecha}|{$i->campo}", collect()) as $p) {
                // Los kilos son de poda/cosecha, no de infestación
                if ($p->unidad === CampoProduccionReglas::UNIDAD_CUADRE) {
                    continue;
                }
                $enCampo[] = ['codigo' => $p->codigo_labor, 'nombre' => $p->nombre_labor, 'unidad' => $p->unidad,
                    'cantidad' => (float) $p->cantidad, 'diferencia' => round((float) $p->cantidad - $envases, 2)];
                $labores[$p->codigo_labor] = $p->nombre_labor;
            }
            $dias[] = ['fecha' => $fecha, 'campo' => $i->campo, 'tipos' => $i->tipos, 'origenes' => $i->origenes,
                'kg_madres' => (float) $i->kg_madres, 'envases' => $envases,
                'kg_por_envase' => $envases > 0 ? round((float) $i->kg_madres / $envases, 2) : null, 'labores' => $enCampo];
        }
        usort($dias, fn($a, $b) => [$b['fecha'], $a['campo']] <=> [$a['fecha'], $b['campo']]);
        ksort($labores);
        return ['dias' => $dias, 'labores' => $labores];
    }

    private static function diferencia(float $campo, float $ingreso): array
    {
        return [
            'diferencia_kg' => round($campo - $ingreso, 2),
            'diferencia_pct' => $ingreso > 0 ? round(($campo - $ingreso) / $ingreso * 100, 1) : null,
            'estado' => CampoProduccionReglas::estadoCuadre($campo, $ingreso),
        ];
    }

    /** Actividad del día, campo y labor (si existe). */
    public static function actividad(string $fecha, string $campo, int $codigoLabor): ?Actividad
    {
        return Actividad::whereDate('fecha', Carbon::parse($fecha)->toDateString())->where('campo', $campo)->where('codigo_labor', $codigoLabor)->first();
    }
}
