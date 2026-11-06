<?php

namespace App\Services\Cuadrilla\Grupo;

use App\Models\CuaGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Lectura de grupos de cuadrilla: listado con su último tramo y la participación por año.
 *
 * El año es el de la semana ISO (la semana del 29/12/2025 al 03/01/2026 cuenta en 2026, igual que su tramo).
 */
class CuadrillaGrupoConsulta
{
    /** Columnas por las que se puede ordenar el listado. */
    public const ORDENES = ['nombre', 'codigo', 'created_at', 'costo_dia_sugerido', 'ultimo_tramo'];

    /**
     * Grupos con: ultimo_tramo (fin del último tramo donde estuvo), tramos (cantidad) y cuadrilleros_count.
     *
     * @param bool $soloInactivos solo los que no están en un tramo hace más de N meses (configurable)
     */
    public function listado(?string $buscar, string $orden, string $direccion, bool $eliminados, bool $soloInactivos): Builder
    {
        $tramos = DB::table('cuad_tramo_grupos as tg')
            ->join('cuad_tramos_laborales as t', 't.id', '=', 'tg.cuad_tramo_laboral_id')
            ->selectRaw('tg.codigo_grupo, MAX(t.fecha_fin) AS ultimo_tramo, COUNT(*) AS tramos')
            ->groupBy('tg.codigo_grupo');

        $query = CuaGrupo::query()
            ->leftJoinSub($tramos, 'ut', 'ut.codigo_grupo', '=', 'cuad_grupos.codigo')
            ->select('cuad_grupos.*', 'ut.ultimo_tramo', DB::raw('COALESCE(ut.tramos, 0) AS tramos'))
            ->withCount('cuadrilleros');

        if ($eliminados) {
            $query->onlyTrashed();
        }
        if ($buscar = trim((string) $buscar)) {
            $query->where(fn($q) => $q->where('cuad_grupos.nombre', 'like', "%{$buscar}%")->orWhere('cuad_grupos.codigo', 'like', "%{$buscar}%"));
        }
        if ($soloInactivos) {
            // Sin tramo desde la fecha límite; si nunca estuvo en uno, cuenta desde su creación
            $query->whereRaw("COALESCE(ut.ultimo_tramo, DATE(cuad_grupos.created_at), '1900-01-01') < ?", [self::fechaLimiteInactivo()]);
        }

        $orden = in_array($orden, self::ORDENES, true) ? $orden : 'created_at';
        $direccion = $direccion === 'asc' ? 'asc' : 'desc';
        $columna = $orden === 'ultimo_tramo' ? 'ut.ultimo_tramo' : "cuad_grupos.{$orden}";

        return $query->orderByRaw("{$columna} IS NULL")->orderBy($columna, $direccion)->orderBy('cuad_grupos.codigo');
    }

    /** Semanas hacia atrás en que un grupo cuenta como "usado recientemente" en el selector. */
    public const SEMANAS_RECIENTE = 8;

    /**
     * Grupos activos para el selector de un tramo, con etiqueta de uso y en este orden:
     * 1) los que ya están en el tramo, 2) los usados en las últimas SEMANAS_RECIENTE semanas (el más reciente primero),
     * 3) el resto por código. Solo cuentan tramos que empiezan antes o igual que el tramo (no los futuros).
     *
     * @return array<int, array{codigo:string, nombre:string, color:?string, seccion:string, etiqueta:string, ultimo:?string}>
     */
    public function paraSelector(?\App\Models\CuadTramoLaboral $tramo): array
    {
        $referencia = $tramo ? \Illuminate\Support\Carbon::parse($tramo->fecha_inicio) : now();
        $enTramo = $tramo
            ? DB::table('cuad_tramo_grupos')->where('cuad_tramo_laboral_id', $tramo->id)->pluck('codigo_grupo')->all()
            : [];
        $ultimos = DB::table('cuad_tramo_grupos as tg')
            ->join('cuad_tramos_laborales as t', 't.id', '=', 'tg.cuad_tramo_laboral_id')
            ->when($tramo, fn($q) => $q->where('t.id', '!=', $tramo->id))
            ->whereDate('t.fecha_inicio', '<=', $referencia->toDateString())
            ->groupBy('tg.codigo_grupo')
            ->selectRaw('tg.codigo_grupo, MAX(t.fecha_inicio) AS ultimo')
            ->pluck('ultimo', 'codigo_grupo');

        $limite = $referencia->copy()->subWeeks(self::SEMANAS_RECIENTE)->toDateString();

        return CuaGrupo::orderBy('codigo')->get(['codigo', 'nombre', 'color'])
            ->map(function ($g) use ($enTramo, $ultimos, $limite, $referencia) {
                $ultimo = $ultimos[$g->codigo] ?? null;
                $ultimo = $ultimo ? \Illuminate\Support\Carbon::parse($ultimo)->toDateString() : null;
                if (in_array($g->codigo, $enTramo, true)) {
                    [$seccion, $etiqueta] = ['tramo', 'En esta semana'];
                } elseif ($ultimo && $ultimo >= $limite) {
                    $semanas = (int) floor(\Illuminate\Support\Carbon::parse($ultimo)->diffInDays($referencia) / 7);
                    $seccion = 'reciente';
                    $etiqueta = $semanas <= 1 ? 'Semana pasada' : "Hace {$semanas} semanas";
                } else {
                    $seccion = 'otros';
                    $etiqueta = $ultimo ? 'Último ' . \Illuminate\Support\Carbon::parse($ultimo)->format('d/m/Y') : 'Sin uso';
                }
                return ['codigo' => $g->codigo, 'nombre' => $g->nombre, 'color' => $g->color,
                    'seccion' => $seccion, 'etiqueta' => $etiqueta, 'ultimo' => $ultimo];
            })
            ->sortBy(fn($g) => [
                ['tramo' => 0, 'reciente' => 1, 'otros' => 2][$g['seccion']],
                $g['seccion'] === 'reciente' ? -strtotime($g['ultimo']) : 0,
                $g['codigo'],
            ])
            ->values()
            ->all();
    }

    /** Antes de esta fecha sin tramo, el grupo se considera inactivo. */
    public static function fechaLimiteInactivo(): string
    {
        return now()->subMonthsNoOverflow(CuadrillaGrupoReglas::mesesSinTramo())->toDateString();
    }

    /**
     * Participación por año y grupo.
     *
     * - semanas: semanas con al menos un cuadrillero con horas en el grupo.
     * - jornal / bonos: lo ganado (jornal por las horas no a destajo + todos los bonos).
     * - gastos: gastos adicionales del grupo (pendientes y aprobados), por la semana de su tramo.
     *
     * @return array<int, array<string, array{codigo:string, nombre:string, color:?string, semanas:int, dias:int, jornal:float, bonos:float, ganado:float, gastos:float}>>
     *               año => código de grupo => datos, años de mayor a menor y grupos por lo ganado
     */
    public function participacionPorAnio(): array
    {
        $anio = 'FLOOR(YEARWEEK(%s, 3) / 100)';

        $registros = DB::table('cuad_registros_diarios as r')
            ->leftJoinSub(
                DB::table('cuad_bonos_actividades')->selectRaw('registro_diario_id, SUM(total_bono) AS bonos')->groupBy('registro_diario_id'),
                'b', 'b.registro_diario_id', '=', 'r.id'
            )
            ->selectRaw(sprintf($anio, 'r.fecha') . ' AS anio, r.codigo_grupo')
            ->selectRaw('COUNT(DISTINCT CASE WHEN r.total_horas > 0 THEN YEARWEEK(r.fecha, 3) END) AS semanas')
            ->selectRaw('COUNT(DISTINCT CASE WHEN r.total_horas > 0 THEN r.fecha END) AS dias')
            ->selectRaw('SUM(GREATEST(0, r.total_horas - COALESCE(r.horas_destajo, 0)) / 8 * COALESCE(NULLIF(r.costo_personalizado_dia, 0), r.jornal_aplicado, 0)) AS jornal')
            ->selectRaw('SUM(COALESCE(b.bonos, 0)) AS bonos')
            ->groupBy('anio', 'r.codigo_grupo')
            ->get();

        $gastos = DB::table('cuad_gastos_grupos as g')
            ->join('cuad_tramos_laborales as t', 't.id', '=', 'g.cuad_tramo_laboral_id')
            ->selectRaw(sprintf($anio, 't.fecha_inicio') . ' AS anio, g.codigo_grupo, SUM(g.monto) AS gastos')
            ->groupBy('anio', 'g.codigo_grupo')
            ->get();

        $grupos = CuaGrupo::withTrashed()->get(['codigo', 'nombre', 'color'])->keyBy('codigo');
        $vacio = fn($codigo) => [
            'codigo' => $codigo,
            'nombre' => $grupos[$codigo]->nombre ?? $codigo,
            'color' => $grupos[$codigo]->color ?? null,
            'semanas' => 0, 'dias' => 0, 'jornal' => 0.0, 'bonos' => 0.0, 'ganado' => 0.0, 'gastos' => 0.0,
        ];

        $datos = [];
        foreach ($registros as $r) {
            $fila = $datos[$r->anio][$r->codigo_grupo] ?? $vacio($r->codigo_grupo);
            $fila['semanas'] = (int) $r->semanas;
            $fila['dias'] = (int) $r->dias;
            $fila['jornal'] = round((float) $r->jornal, 2);
            $fila['bonos'] = round((float) $r->bonos, 2);
            $fila['ganado'] = round($fila['jornal'] + $fila['bonos'], 2);
            $datos[$r->anio][$r->codigo_grupo] = $fila;
        }
        foreach ($gastos as $g) {
            $fila = $datos[$g->anio][$g->codigo_grupo] ?? $vacio($g->codigo_grupo);
            $fila['gastos'] = round((float) $g->gastos, 2);
            $datos[$g->anio][$g->codigo_grupo] = $fila;
        }

        krsort($datos);
        foreach ($datos as &$porGrupo) {
            uasort($porGrupo, fn($a, $b) => [$b['ganado'], $b['semanas']] <=> [$a['ganado'], $a['semanas']]);
        }
        return $datos;
    }
}
