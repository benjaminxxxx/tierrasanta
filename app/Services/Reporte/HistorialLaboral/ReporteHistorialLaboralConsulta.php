<?php

namespace App\Services\Reporte\HistorialLaboral;

use App\Services\Campo\Labor\CampoLaborVigenciaConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Historial laboral de un trabajador: día a día, qué hizo en planilla y en cuadrilla (campos, labores, horarios, con
 * cuántos compañeros), su producción contra el estándar y lo que costó.
 *
 * Un planillero puede trabajar también en cuadrilla (feriados, tardes: "trabajo extra" que no entra en planilla) con
 * otro nombre (el Excel antiguo guardaba el nombre como lo dictaba: "Juan Quispe", "Antonio Quispe"…). Los
 * cuadrilleros no están ligados a personas, así que se buscan por nombre parcial y se eligen varias identidades a la
 * vez: claves "p:{plan_empleado_id}" y "c:{cuad_cuadrillero_id}".
 *
 * Estándar de producción: el del método de la actividad (actividad_metodos.estandar) o, si no tiene, el de la labor.
 * Se compara la producción total del trabajador en esa actividad (suma de sus recojos).
 */
class ReporteHistorialLaboralConsulta
{
    public function __construct(private CampoLaborVigenciaConsulta $labores)
    {
    }

    // ------------------------------------------------------------------ búsqueda

    /**
     * Planilleros (y cuadrilleros si se pide) cuyo nombre o documento contiene TODAS las palabras escritas.
     *
     * @return array<int, array{clave:string, tipo:string, nombre:string, documento:?string, detalle:string}>
     */
    public function buscar(string $texto, bool $incluirCuadrilla): array
    {
        $palabras = $this->palabras($texto);
        if (!$palabras) {
            return [];
        }
        $resultado = [];

        $empleados = DB::table('plan_empleados')
            ->where(function ($q) use ($palabras) {
                foreach ($palabras as $p) {
                    $q->whereRaw("CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno, documento) LIKE ?", ["%{$p}%"]);
                }
            })
            ->orderBy('apellido_paterno')->limit(30)
            ->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'documento', 'deleted_at']);
        $ultimos = $empleados->isEmpty() ? collect() : DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->whereIn('d.plan_empleado_id', $empleados->pluck('id'))
            ->groupBy('d.plan_empleado_id')->selectRaw('d.plan_empleado_id, MAX(r.fecha) AS ultima')->pluck('ultima', 'plan_empleado_id');
        foreach ($empleados as $e) {
            $resultado[] = [
                'clave' => "p:{$e->id}",
                'tipo' => 'planilla',
                'nombre' => trim("{$e->apellido_paterno} {$e->apellido_materno}, {$e->nombres}"),
                'documento' => $e->documento,
                'detalle' => 'Planilla' . ($e->deleted_at ? ' (eliminado)' : '')
                    . (isset($ultimos[$e->id]) ? ' · último registro ' . Carbon::parse($ultimos[$e->id])->format('d/m/Y') : ''),
            ];
        }

        if ($incluirCuadrilla) {
            $cuadrilleros = DB::table('cuad_cuadrilleros')
                ->where(function ($q) use ($palabras) {
                    foreach ($palabras as $p) {
                        $q->whereRaw("CONCAT_WS(' ', nombres, dni) LIKE ?", ["%{$p}%"]);
                    }
                })
                ->orderBy('nombres')->limit(40)->get(['id', 'nombres', 'dni', 'codigo_grupo', 'deleted_at']);
            $resultado = array_merge($resultado, $this->filasCuadrilleros($cuadrilleros));
        }
        return $resultado;
    }

    /**
     * Cuadrilleros que podrían ser el mismo planillero: mismo DNI, o su apellido paterno y al menos otro de sus
     * nombres/apellidos en el nombre del cuadrillero (el nombre en cuadrilla suele estar incompleto).
     */
    public function posiblesEnCuadrilla(int $planEmpleadoId): array
    {
        $e = DB::table('plan_empleados')->find($planEmpleadoId);
        if (!$e) {
            return [];
        }
        $paterno = $this->norm($e->apellido_paterno);
        $otros = array_values(array_filter(array_merge($this->palabras($e->nombres), $this->palabras($e->apellido_materno)), fn($p) => mb_strlen($p) >= 3));

        $candidatos = DB::table('cuad_cuadrilleros')
            ->where(fn($q) => $q->when($e->documento, fn($q) => $q->where('dni', $e->documento))
                ->orWhere('nombres', 'like', "%{$e->apellido_paterno}%"))
            ->get(['id', 'nombres', 'dni', 'codigo_grupo', 'deleted_at'])
            ->map(function ($c) use ($e, $paterno, $otros) {
                $nombre = $this->norm($c->nombres);
                $c->puntos = ($e->documento && $c->dni === $e->documento) ? 100
                    : (str_contains($nombre, $paterno) ? 1 + count(array_filter($otros, fn($p) => str_contains($nombre, $p))) : 0);
                return $c;
            })
            ->filter(fn($c) => $c->puntos >= 2)
            ->sortByDesc('puntos')->take(15)->values();
        return $this->filasCuadrilleros($candidatos);
    }

    /** Datos de las identidades elegidas ("p:{id}" / "c:{id}"), en el mismo formato que buscar(). */
    public function identidades(array $claves): array
    {
        $ids = ['p' => [], 'c' => []];
        foreach ($claves as $c) {
            [$tipo, $id] = explode(':', $c) + [null, null];
            if (isset($ids[$tipo])) {
                $ids[$tipo][] = (int) $id;
            }
        }
        $resultado = [];
        foreach (DB::table('plan_empleados')->whereIn('id', $ids['p'])->get() as $e) {
            $resultado["p:{$e->id}"] = [
                'clave' => "p:{$e->id}", 'tipo' => 'planilla',
                'nombre' => trim("{$e->apellido_paterno} {$e->apellido_materno}, {$e->nombres}"),
                'documento' => $e->documento, 'detalle' => 'Planilla',
            ];
        }
        foreach ($this->filasCuadrilleros(DB::table('cuad_cuadrilleros')->whereIn('id', $ids['c'])->get(['id', 'nombres', 'dni', 'codigo_grupo', 'deleted_at'])) as $c) {
            $resultado[$c['clave']] = $c;
        }
        // En el orden en que se eligieron
        return array_values(array_filter(array_map(fn($c) => $resultado[$c] ?? null, $claves)));
    }

    private function filasCuadrilleros(Collection $cuadrilleros): array
    {
        if ($cuadrilleros->isEmpty()) {
            return [];
        }
        $uso = DB::table('cuad_registros_diarios')->whereIn('cuadrillero_id', $cuadrilleros->pluck('id'))
            ->groupBy('cuadrillero_id')->selectRaw('cuadrillero_id, COUNT(*) AS dias, MIN(fecha) AS desde, MAX(fecha) AS hasta')
            ->get()->keyBy('cuadrillero_id');
        return $cuadrilleros->map(fn($c) => [
            'clave' => "c:{$c->id}",
            'tipo' => 'cuadrilla',
            'nombre' => $c->nombres,
            'documento' => $c->dni ?: null,
            'detalle' => 'Cuadrilla ' . ($c->codigo_grupo ?? '') . ($c->deleted_at ? ' (eliminado)' : '')
                . (isset($uso[$c->id]) ? ' · ' . $uso[$c->id]->dias . ' días entre ' . Carbon::parse($uso[$c->id]->desde)->format('m/Y')
                    . ' y ' . Carbon::parse($uso[$c->id]->hasta)->format('m/Y') : ' · sin registros'),
        ])->values()->all();
    }

    // ------------------------------------------------------------------ historial

    /**
     * @param string[] $claves "p:{id}" / "c:{id}"
     * @return array{dias: array, resumen: array, identidades: array}
     */
    public function historial(array $claves, string $desde, string $hasta): array
    {
        $planIds = [];
        $cuadIds = [];
        foreach ($claves as $c) {
            [$tipo, $id] = explode(':', $c) + [null, null];
            $tipo === 'p' ? $planIds[] = (int) $id : ($tipo === 'c' ? $cuadIds[] = (int) $id : null);
        }

        $entradas = array_merge($this->planilla($planIds, $desde, $hasta), $this->cuadrilla($cuadIds, $desde, $hasta));
        $this->agregarCompanieros($entradas, $desde, $hasta);
        foreach ($entradas as &$e) {
            $e['texto'] = $this->narrar($e);
        }
        unset($e);
        usort($entradas, fn($a, $b) => [$a['fecha'], $a['origen']] <=> [$b['fecha'], $b['origen']]);

        $dias = [];
        foreach ($entradas as $e) {
            $dias[$e['fecha']] ??= ['fecha' => $e['fecha'], 'entradas' => [], 'doble' => false];
            $dias[$e['fecha']]['entradas'][] = $e;
        }
        foreach ($dias as &$d) {
            $origenes = array_unique(array_column($d['entradas'], 'origen'));
            $trabajoPlanilla = collect($d['entradas'])->contains(fn($e) => $e['origen'] === 'planilla' && $e['horas'] > 0);
            $d['doble'] = count($origenes) > 1 && $trabajoPlanilla;
        }
        unset($d);

        return [
            'dias' => array_values($dias),
            'resumen' => $this->resumen($entradas, $dias),
        ];
    }

    private function planilla(array $ids, string $desde, string $hasta): array
    {
        if (!$ids) {
            return [];
        }
        $registros = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->leftJoin('plan_empleados as e', 'e.id', '=', 'd.plan_empleado_id')
            ->leftJoin('personas as pe', 'pe.id', '=', 'e.persona_id')
            ->whereIn('d.plan_empleado_id', $ids)->whereBetween('r.fecha', [$desde, $hasta])
            ->get(['r.id', 'r.fecha', 'r.asistencia', 'r.total_horas', 'd.plan_empleado_id',
                DB::raw("TRIM(CONCAT_WS(' ', pe.apellido_paterno, pe.apellido_materno, ',', pe.nombres)) AS nombre")]);
        if ($registros->isEmpty()) {
            return [];
        }
        $tipos = DB::table('plan_tipo_asistencias')->pluck('descripcion', 'codigo');
        $tramos = DB::table('plan_detalles_horas')->whereIn('plan_reg_dia_id', $registros->pluck('id'))
            ->orderBy('hora_inicio')->get(['plan_reg_dia_id as registro', 'campo_nombre', 'codigo_labor', 'hora_inicio', 'hora_fin'])->groupBy('registro');
        $bonos = $this->bonos('plan_actividad_bonos', $registros->pluck('plan_empleado_id', 'id'), 'plan_empleado_id');
        // Costo real del día: lo que la BDD le asigna (sueldo + aportes prorrateados por hora, y bonos)
        $costos = DB::table('resumen_costo_diarios')->whereIn('plan_empleado_id', $ids)->whereBetween('fecha', [$desde, $hasta])
            ->whereIn('origen_tipo', ['planilla', 'planilla_bono_productividad'])
            ->groupBy('plan_empleado_id', 'fecha')->selectRaw('plan_empleado_id, fecha, SUM(costo_total) AS costo')
            ->get()->keyBy(fn($c) => $c->plan_empleado_id . '|' . Carbon::parse($c->fecha)->toDateString());

        return $registros->map(function ($r) use ($tipos, $tramos, $bonos, $costos) {
            $fecha = Carbon::parse($r->fecha)->toDateString();
            return [
                'fecha' => $fecha,
                'origen' => 'planilla',
                'quien' => $r->nombre,
                'asistencia' => $r->asistencia,
                'asistencia_texto' => $tipos[$r->asistencia] ?? $r->asistencia,
                'horas' => (float) $r->total_horas,
                'grupo' => null,
                'costo' => isset($costos["{$r->plan_empleado_id}|{$fecha}"]) ? round((float) $costos["{$r->plan_empleado_id}|{$fecha}"]->costo, 2) : null,
                'tramos' => $this->tramos($tramos->get($r->id, collect()), $fecha),
                'producciones' => $bonos->get($r->id, []),
            ];
        })->all();
    }

    private function cuadrilla(array $ids, string $desde, string $hasta): array
    {
        if (!$ids) {
            return [];
        }
        $registros = DB::table('cuad_registros_diarios as r')->join('cuad_cuadrilleros as c', 'c.id', '=', 'r.cuadrillero_id')
            ->whereIn('r.cuadrillero_id', $ids)->whereBetween('r.fecha', [$desde, $hasta])
            ->get(['r.id', 'r.fecha', 'r.total_horas', 'r.horas_destajo', 'r.jornal_aplicado', 'r.costo_personalizado_dia', 'r.total_bono', 'r.codigo_grupo', 'c.nombres', 'r.cuadrillero_id']);
        if ($registros->isEmpty()) {
            return [];
        }
        $tramos = DB::table('cuad_detalles_horas')->whereIn('registro_diario_id', $registros->pluck('id'))
            ->orderBy('hora_inicio')->get(['registro_diario_id as registro', 'campo_nombre', 'codigo_labor', 'hora_inicio', 'hora_fin'])->groupBy('registro');
        $bonos = $this->bonos('cuad_bonos_actividades', $registros->pluck('cuadrillero_id', 'id'), 'cuadrillero_id');

        return $registros->map(function ($r) use ($tramos, $bonos) {
            $fecha = Carbon::parse($r->fecha)->toDateString();
            // Jornal por las horas no destajo (como el resumen del grupo) + bonos
            $jornal = (float) ($r->costo_personalizado_dia ?: $r->jornal_aplicado);
            $costo = max(0, (float) $r->total_horas - (float) $r->horas_destajo) / 8 * $jornal + (float) $r->total_bono;
            return [
                'fecha' => $fecha,
                'origen' => 'cuadrilla',
                'quien' => $r->nombres,
                'asistencia' => null,
                'asistencia_texto' => null,
                'horas' => (float) $r->total_horas,
                'grupo' => $r->codigo_grupo,
                'costo' => round($costo, 2),
                'tramos' => $this->tramos($tramos->get($r->id, collect()), $fecha),
                'producciones' => $bonos->get($r->id, []),
            ];
        })->all();
    }

    private function tramos(Collection $filas, string $fecha): array
    {
        return $filas->map(function ($t) use ($fecha) {
            $inicio = substr((string) $t->hora_inicio, 0, 5);
            $fin = substr((string) $t->hora_fin, 0, 5);
            $min = $inicio && $fin ? (Carbon::parse($fin)->diffInMinutes(Carbon::parse($inicio), true)) : 0;
            return [
                'campo' => $t->campo_nombre,
                'codigo_labor' => $t->codigo_labor,
                'labor' => $this->labores->nombre($t->codigo_labor, $fecha) ?? ($t->codigo_labor ? "Labor {$t->codigo_labor}" : 'Sin labor'),
                'inicio' => $inicio,
                'fin' => $fin,
                'horas' => round($min / 60, 2),
                'companieros' => null,
            ];
        })->values()->all();
    }

    /**
     * Producción por actividad (bonos) de los registros, con su estándar. Las cantidades vienen del registro de producción
     * de campo (por actividad y trabajador).
     * @param Collection $trabajadorPorRegistro registro_diario_id => id del trabajador
     * @param string $columna columna del trabajador en produccion_registros (cuadrillero_id / plan_empleado_id)
     */
    private function bonos(string $tablaBonos, Collection $trabajadorPorRegistro, string $columna): Collection
    {
        $bonos = DB::table("{$tablaBonos} as b")->join('actividades as a', 'a.id', '=', 'b.actividad_id')
            ->leftJoin('actividad_metodos as m', 'm.id', '=', 'b.metodo_id')
            ->leftJoin('labores as l', 'l.id', '=', 'a.labor_id')
            ->whereIn('b.registro_diario_id', $trabajadorPorRegistro->keys())
            ->get(['b.id', 'b.registro_diario_id', 'b.actividad_id', 'b.total_bono', 'a.fecha', 'a.campo', 'a.nombre_labor', 'a.codigo_labor', 'a.unidades',
                'm.estandar', 'l.estandar_produccion', 'l.unidades as labor_unidades']);
        if ($bonos->isEmpty()) {
            return collect();
        }
        $producciones = DB::table('produccion_registros')->whereIn('actividad_id', $bonos->pluck('actividad_id')->unique())
            ->whereIn($columna, $trabajadorPorRegistro->values()->filter()->unique())->where('con_bono', true)
            ->orderBy('numero_recojo')->get(['actividad_id', $columna, 'numero_recojo', 'cantidad'])
            ->groupBy(fn($p) => $p->actividad_id . '|' . $p->{$columna});

        return $bonos->map(function ($b) use ($producciones, $trabajadorPorRegistro) {
            $clave = $b->actividad_id . '|' . $trabajadorPorRegistro[$b->registro_diario_id];
            $recojos = $producciones->get($clave, collect())->map(fn($p) => (float) $p->cantidad)->values()->all();
            $total = array_sum($recojos);
            $estandar = $b->estandar !== null ? (float) $b->estandar : ($b->estandar_produccion !== null ? (float) $b->estandar_produccion : null);
            $pct = $estandar && $recojos ? round($total / $estandar * 100, 1) : null;
            return [
                'registro' => $b->registro_diario_id,
                'campo' => $b->campo,
                'labor' => $this->labores->nombre($b->codigo_labor, $b->fecha) ?? $b->nombre_labor,
                'unidades' => $b->unidades ?: $b->labor_unidades,
                'recojos' => $recojos,
                'produccion' => round($total, 2),
                'estandar' => $estandar,
                'porcentaje' => $pct,
                'estado' => $pct === null ? null : ($pct > 100 ? 'sobre' : ($pct < 100 ? 'debajo' : 'igual')),
                'bono' => round((float) $b->total_bono, 2),
            ];
        })->groupBy('registro')->map(fn($g) => $g->values()->all());
    }

    /** Cuántos otros trabajadores (planilla + cuadrilla) hicieron la misma labor en el mismo campo ese día. */
    private function agregarCompanieros(array &$entradas, string $desde, string $hasta): void
    {
        $claves = [];
        foreach ($entradas as $e) {
            foreach ($e['tramos'] as $t) {
                $claves["{$e['fecha']}|{$t['campo']}|{$t['codigo_labor']}"] = true;
            }
        }
        if (!$claves) {
            return;
        }
        $campos = array_unique(array_map(fn($k) => explode('|', $k)[1], array_keys($claves)));
        $conteo = [];
        foreach ([['plan_detalles_horas', 'plan_reg_dia_id', 'plan_registros_diarios'], ['cuad_detalles_horas', 'registro_diario_id', 'cuad_registros_diarios']] as [$detalle, $fk, $registro]) {
            DB::table("{$detalle} as h")->join("{$registro} as r", 'r.id', '=', "h.{$fk}")
                ->whereBetween('r.fecha', [$desde, $hasta])->whereIn('h.campo_nombre', $campos)
                ->groupBy('r.fecha', 'h.campo_nombre', 'h.codigo_labor')
                ->selectRaw("r.fecha, h.campo_nombre, h.codigo_labor, COUNT(DISTINCT h.{$fk}) AS n")
                ->get()->each(function ($f) use (&$conteo) {
                    $k = Carbon::parse($f->fecha)->toDateString() . "|{$f->campo_nombre}|{$f->codigo_labor}";
                    $conteo[$k] = ($conteo[$k] ?? 0) + (int) $f->n;
                });
        }
        foreach ($entradas as &$e) {
            foreach ($e['tramos'] as &$t) {
                $t['companieros'] = max(0, ($conteo["{$e['fecha']}|{$t['campo']}|{$t['codigo_labor']}"] ?? 1) - 1);
            }
            unset($t);
        }
        unset($e);
    }

    /** "Planilla: asistió, 8 h. 07:00–11:00 en el campo 3: «Poda mamá» con 5 más; …" */
    private function narrar(array $e): string
    {
        $partes = [];
        $cabecera = $e['origen'] === 'planilla'
            ? 'Planilla' . ($e['asistencia'] && $e['asistencia'] !== 'A' ? " ({$e['asistencia_texto']})" : '')
            : 'Cuadrilla' . ($e['grupo'] ? " {$e['grupo']}" : '') . " como «{$e['quien']}»";
        $partes[] = $cabecera . ': ' . $this->horas($e['horas']) . '.';
        foreach ($e['tramos'] as $t) {
            $partes[] = ($t['inicio'] && $t['fin'] ? "{$t['inicio']}–{$t['fin']} " : '') . "campo {$t['campo']}: «{$t['labor']}»"
                . ($t['companieros'] ? " con {$t['companieros']} trabajador" . ($t['companieros'] === 1 ? '' : 'es') . ' más' : ' solo') . '.';
        }
        foreach ($e['producciones'] as $p) {
            if (!$p['recojos']) {
                continue;
            }
            $u = $p['unidades'] ? " {$p['unidades']}" : '';
            $texto = "Produjo " . $this->num($p['produccion']) . "{$u} en «{$p['labor']}»"
                . (count($p['recojos']) > 1 ? ' (' . implode(' + ', array_map(fn($r) => $this->num($r), $p['recojos'])) . ')' : '');
            if ($p['estandar']) {
                $dif = $p['porcentaje'] - 100;
                $texto .= ': estándar ' . $this->num($p['estandar']) . "{$u}, "
                    . ($p['estado'] === 'igual' ? 'justo en el estándar' : ($dif > 0 ? '+' : '') . $this->num($dif) . ' % (' . ($p['estado'] === 'sobre' ? 'sobre el' : 'debajo del') . ' estándar)');
            }
            $partes[] = $texto . ($p['bono'] > 0 ? '; bono S/ ' . number_format($p['bono'], 2) : '') . '.';
        }
        return implode(' ', $partes);
    }

    private function resumen(array $entradas, array $dias): array
    {
        $col = collect($entradas);
        $producciones = $col->flatMap(fn($e) => $e['producciones'])->filter(fn($p) => $p['porcentaje'] !== null);
        $porLabor = $col->flatMap(fn($e) => $e['tramos'])->groupBy('labor')
            ->map(fn($g, $labor) => ['labor' => $labor, 'horas' => round($g->sum('horas'), 2), 'veces' => $g->count()])
            ->sortByDesc('horas')->values()->all();
        $porCampo = $col->flatMap(fn($e) => $e['tramos'])->groupBy('campo')
            ->map(fn($g, $campo) => ['campo' => $campo, 'horas' => round($g->sum('horas'), 2)])->sortByDesc('horas')->values()->all();
        $prodPorLabor = $producciones->groupBy('labor')->map(fn($g, $labor) => [
            'labor' => $labor,
            'unidades' => $g->first()['unidades'],
            'veces' => $g->count(),
            'sobre' => $g->where('estado', 'sobre')->count(),
            'debajo' => $g->where('estado', 'debajo')->count(),
            'promedio_pct' => round($g->avg('porcentaje'), 1),
            'produccion_promedio' => round($g->avg('produccion'), 2),
            'estandar' => $g->pluck('estandar')->filter()->unique()->implode(' / '),
        ])->sortByDesc('veces')->values()->all();

        return [
            'dias_planilla' => $col->where('origen', 'planilla')->filter(fn($e) => $e['horas'] > 0)->pluck('fecha')->unique()->count(),
            'dias_cuadrilla' => $col->where('origen', 'cuadrilla')->pluck('fecha')->unique()->count(),
            'dias_doble' => collect($dias)->where('doble', true)->count(),
            'horas_planilla' => round($col->where('origen', 'planilla')->sum('horas'), 2),
            'horas_cuadrilla' => round($col->where('origen', 'cuadrilla')->sum('horas'), 2),
            'costo_planilla' => round($col->where('origen', 'planilla')->sum('costo'), 2),
            'costo_cuadrilla' => round($col->where('origen', 'cuadrilla')->sum('costo'), 2),
            'ausencias' => $col->where('origen', 'planilla')->filter(fn($e) => $e['asistencia'] && $e['asistencia'] !== 'A')
                ->groupBy('asistencia_texto')->map->count()->all(),
            'por_labor' => $porLabor,
            'por_campo' => $porCampo,
            'produccion' => [
                'evaluadas' => $producciones->count(),
                'sobre' => $producciones->where('estado', 'sobre')->count(),
                'debajo' => $producciones->where('estado', 'debajo')->count(),
                'igual' => $producciones->where('estado', 'igual')->count(),
                'promedio_pct' => $producciones->isNotEmpty() ? round($producciones->avg('porcentaje'), 1) : null,
                'por_labor' => $prodPorLabor,
            ],
        ];
    }

    // ------------------------------------------------------------------ utilidades

    private function palabras(?string $texto): array
    {
        return array_values(array_filter(preg_split('/[\s,]+/', $this->norm($texto)), fn($p) => mb_strlen($p) >= 2));
    }

    private function norm(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        return strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }

    private function horas(float $h): string
    {
        return $h == (int) $h ? (int) $h . ' h' : number_format($h, 1) . ' h';
    }

    private function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }
}
