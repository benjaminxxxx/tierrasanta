<?php

namespace App\Services\Planilla\Suspension;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanSuspension;
use App\Models\PlanTipoSuspension;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lectura de suspensiones: agrupadas por trabajador para el mes (Permisos y suspensiones) y estadísticas
 * (por mes del año, por día del mes, historial de un trabajador), filtrables por tipo de planilla.
 */
class PlanillaSuspensionConsulta
{
    /** Colores por código SUNAT (mismos en la línea del mes y en los gráficos) */
    private const COLORES = [
        '01' => '#64748B', '02' => '#94A3B8', '03' => '#78716C', '04' => '#A8A29E', '05' => '#F59E0B',
        '06' => '#EAB308', '07' => '#DC2626', '08' => '#B91C1C',
        '20' => '#0EA5E9', '21' => '#2563EB', '22' => '#7C3AED', '23' => '#16A34A', '24' => '#DB2777',
        '25' => '#0D9488', '26' => '#9333EA', '27' => '#CA8A04',
    ];

    public static function color(?string $codigo): string
    {
        return self::COLORES[$codigo] ?? '#6B7280';
    }

    /** @return array<int, array{id:int, codigo:string, descripcion:string, label:string, color:string}> */
    public function tipos(): array
    {
        return PlanTipoSuspension::orderBy('codigo')->get()->map(fn($t) => [
            'id' => $t->id, 'codigo' => $t->codigo, 'descripcion' => $t->descripcion,
            'label' => $t->codigo . ' - ' . $t->descripcion, 'color' => self::color($t->codigo),
        ])->all();
    }

    /**
     * Tipo de planilla de cada trabajador según su contrato vigente en el rango (el más reciente).
     *
     * @return array<int, string> plan_empleado_id => agraria|oficina|general
     */
    public function tipoPlanillaPorEmpleado(Carbon $inicio, Carbon $fin, ?array $empleadosIds = null): array
    {
        return PlanContrato::query()
            ->when($empleadosIds !== null, fn($q) => $q->whereIn('plan_empleado_id', $empleadosIds))
            ->whereDate('fecha_inicio', '<=', $fin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))
            ->orderBy('fecha_inicio')
            ->get(['plan_empleado_id', 'tipo_planilla'])
            ->pluck('tipo_planilla', 'plan_empleado_id')
            ->all();
    }

    /** Trabajadores con contrato en el mes (para agregar suspensiones), con su tipo de planilla. */
    public function empleadosDelMes(int $mes, int $anio): array
    {
        [$inicio, $fin] = $this->rangoMes($mes, $anio);
        $tipos = $this->tipoPlanillaPorEmpleado($inicio, $fin);
        return PlanEmpleado::whereIn('id', array_keys($tipos))->get()
            ->map(fn($e) => ['id' => $e->id, 'label' => $e->nombre_completo . ' · ' . ($tipos[$e->id] ?? ''), 'documento' => $e->documento])
            ->sortBy('label')->values()->all();
    }

    /**
     * Trabajadores con suspensiones que tocan el mes, con lo que tienen ese mes.
     *
     * @return Collection<int, array{plan_empleado_id:int, nombre:string, documento:?string, tipo_planilla:?string,
     *   resumen: array<int, array{codigo:string, descripcion:string, color:string, dias:int}>, dias: array<int, ?string>, total:int, rangos:int}>
     */
    public function porTrabajador(int $mes, int $anio, ?string $buscar = null, ?string $tipoPlanilla = null): Collection
    {
        [$inicio, $fin] = $this->rangoMes($mes, $anio);
        $suspensiones = $this->suspensionesEnRango($inicio, $fin);
        $tipos = $this->tipoPlanillaPorEmpleado($inicio, $fin, $suspensiones->pluck('plan_empleado_id')->unique()->all());
        $termino = mb_strtolower(trim((string) $buscar));

        return $suspensiones->groupBy('plan_empleado_id')->map(function ($lista, $empleadoId) use ($inicio, $fin, $tipos) {
            $empleado = $lista->first()->empleado;
            $dias = array_fill(1, $inicio->daysInMonth, null);
            $resumen = [];
            foreach ($lista as $s) {
                $codigo = $s->tipoSuspension?->codigo;
                foreach ($this->diasDentro($s, $inicio, $fin) as $dia) {
                    $dias[$dia->day] = $codigo;
                    $resumen[$codigo] ??= ['codigo' => $codigo, 'descripcion' => $s->tipoSuspension?->descripcion, 'color' => self::color($codigo), 'dias' => 0];
                    $resumen[$codigo]['dias']++;
                }
            }
            ksort($resumen);
            return [
                'plan_empleado_id' => (int) $empleadoId,
                'nombre' => $empleado?->nombre_completo ?? "Empleado #{$empleadoId}",
                'documento' => $empleado?->documento,
                'tipo_planilla' => $tipos[$empleadoId] ?? null,
                'resumen' => array_values($resumen),
                'dias' => $dias,
                'total' => array_sum(array_column($resumen, 'dias')),
                'rangos' => $lista->count(),
            ];
        })
            ->filter(fn($t) => (!$tipoPlanilla || $t['tipo_planilla'] === $tipoPlanilla)
                && ($termino === '' || str_contains(mb_strtolower($t['nombre']), $termino) || str_contains((string) $t['documento'], $termino)))
            ->sortBy('nombre')->values();
    }

    /**
     * Rangos de un trabajador para editar en el modal: los que tocan el mes y los futuros (lo ya programado).
     *
     * @return array<int, array{id:int, tipo_suspension_id:int, fecha_inicio:string, fecha_fin:?string, observaciones:?string}>
     */
    public function rangosParaEditar(int $planEmpleadoId, int $mes, int $anio): array
    {
        [$inicio] = $this->rangoMes($mes, $anio);
        return PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))
            ->orderBy('fecha_inicio')->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'tipo_suspension_id' => $s->tipo_suspension_id,
                'fecha_inicio' => $s->fecha_inicio->toDateString(),
                'fecha_fin' => $s->fecha_fin?->toDateString(),
                'observaciones' => $s->observaciones,
            ])->all();
    }

    // ------------------------------------------------------------------ estadísticas

    /**
     * Días de suspensión por mes del año y tipo (gráfico anual).
     *
     * @return array{etiquetas: string[], series: array<int, array{codigo:string, label:string, color:string, datos:int[]}>, total:int}
     */
    public function anual(int $anio, ?string $tipoPlanilla = null, ?int $empleadoId = null): array
    {
        $inicio = Carbon::create($anio, 1, 1)->startOfDay();
        $fin = $inicio->copy()->endOfYear()->startOfDay();
        $porTipo = [];
        foreach ($this->filtradas($inicio, $fin, $tipoPlanilla, $empleadoId) as $s) {
            $codigo = $s->tipoSuspension?->codigo;
            $porTipo[$codigo] ??= ['codigo' => $codigo, 'label' => $codigo . ' ' . $s->tipoSuspension?->descripcion, 'color' => self::color($codigo), 'datos' => array_fill(0, 12, 0)];
            foreach ($this->diasDentro($s, $inicio, $fin) as $dia) {
                $porTipo[$codigo]['datos'][$dia->month - 1]++;
            }
        }
        return $this->armar(array_map(fn($m) => ucfirst(Carbon::create($anio, $m, 1)->translatedFormat('M')), range(1, 12)), $porTipo);
    }

    /**
     * Trabajadores suspendidos por día del mes y tipo (gráfico mensual).
     */
    public function mensual(int $mes, int $anio, ?string $tipoPlanilla = null, ?int $empleadoId = null): array
    {
        [$inicio, $fin] = $this->rangoMes($mes, $anio);
        $n = $inicio->daysInMonth;
        $porTipo = [];
        foreach ($this->filtradas($inicio, $fin, $tipoPlanilla, $empleadoId) as $s) {
            $codigo = $s->tipoSuspension?->codigo;
            $porTipo[$codigo] ??= ['codigo' => $codigo, 'label' => $codigo . ' ' . $s->tipoSuspension?->descripcion, 'color' => self::color($codigo), 'datos' => array_fill(0, $n, 0)];
            foreach ($this->diasDentro($s, $inicio, $fin) as $dia) {
                $porTipo[$codigo]['datos'][$dia->day - 1]++;
            }
        }
        $etiquetas = array_map(fn($d) => $d . ' ' . mb_substr(Carbon::create($anio, $mes, $d)->translatedFormat('D'), 0, 2), range(1, $n));
        return $this->armar($etiquetas, $porTipo);
    }

    /**
     * Historial completo de un trabajador.
     *
     * @return array{rangos: array, por_anio: array<int, array<string, int>>}
     */
    public function historial(int $planEmpleadoId): array
    {
        $lista = PlanSuspension::with('tipoSuspension')->where('plan_empleado_id', $planEmpleadoId)->orderByDesc('fecha_inicio')->get();
        $porAnio = [];
        $rangos = $lista->map(function ($s) use (&$porAnio) {
            $fin = $s->fecha_fin ?? now();
            for ($d = $s->fecha_inicio->copy(); $d->lte($fin); $d->addDay()) {
                $porAnio[$d->year][$s->tipoSuspension?->codigo] = ($porAnio[$d->year][$s->tipoSuspension?->codigo] ?? 0) + 1;
            }
            return [
                'codigo' => $s->tipoSuspension?->codigo,
                'descripcion' => $s->tipoSuspension?->descripcion,
                'color' => self::color($s->tipoSuspension?->codigo),
                'fecha_inicio' => $s->fecha_inicio->toDateString(),
                'fecha_fin' => $s->fecha_fin?->toDateString(),
                'dias' => $s->duracion_dias,
                'observaciones' => $s->observaciones,
            ];
        })->all();
        krsort($porAnio);
        return ['rangos' => $rangos, 'por_anio' => $porAnio];
    }

    // ------------------------------------------------------------------ apoyo

    private function armar(array $etiquetas, array $porTipo): array
    {
        ksort($porTipo);
        $series = array_values($porTipo);
        return ['etiquetas' => $etiquetas, 'series' => $series, 'total' => array_sum(array_map(fn($s) => array_sum($s['datos']), $series))];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rangoMes(int $mes, int $anio): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        return [$inicio, $inicio->copy()->endOfMonth()->startOfDay()];
    }

    private function suspensionesEnRango(Carbon $inicio, Carbon $fin): Collection
    {
        return PlanSuspension::with(['tipoSuspension', 'empleado'])
            ->whereDate('fecha_inicio', '<=', $fin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))
            ->get();
    }

    private function filtradas(Carbon $inicio, Carbon $fin, ?string $tipoPlanilla, ?int $empleadoId): Collection
    {
        $lista = $this->suspensionesEnRango($inicio, $fin);
        if ($empleadoId) {
            $lista = $lista->where('plan_empleado_id', $empleadoId);
        }
        if ($tipoPlanilla) {
            $tipos = $this->tipoPlanillaPorEmpleado($inicio, $fin, $lista->pluck('plan_empleado_id')->unique()->all());
            $lista = $lista->filter(fn($s) => ($tipos[$s->plan_empleado_id] ?? null) === $tipoPlanilla);
        }
        return $lista;
    }

    /** Días de la suspensión dentro del rango (una suspensión sin fin llega hasta hoy). */
    private function diasDentro(PlanSuspension $s, Carbon $inicio, Carbon $fin): array
    {
        $desde = $s->fecha_inicio->copy()->startOfDay()->max($inicio);
        $hasta = ($s->fecha_fin ?? now())->copy()->startOfDay()->min($fin);
        $dias = [];
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            $dias[] = $d->copy();
        }
        return $dias;
    }
}
