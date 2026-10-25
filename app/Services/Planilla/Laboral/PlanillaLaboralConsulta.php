<?php

namespace App\Services\Planilla\Laboral;

use App\Models\PlanContrato;
use App\Models\PlanContratoAsignacion;
use App\Models\PlanContratoRemuneracion;
use App\Models\PlanEmpleado;
use App\Models\PlanEmpleadoPago;
use App\Models\PlanEmpleadoPension;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Única fuente de las reglas de lectura del historial laboral. Todo el sistema (planilla, asistencia, PLAME, perfil,
 * Excel) pregunta aquí; si una regla cambia (qué día representa al mes, prorrateo, etc.) se cambia solo aquí.
 *
 * Modelo:
 * - Contrato: periodo legal con inicio y fin. Puede haber días sin contrato entre dos contratos.
 * - Partículas del contrato (asignación: cargo y grupo; remuneración: sueldo, básica, bonificación…): solo "desde";
 *   valen hasta la siguiente del mismo contrato o hasta el fin del contrato.
 * - Partículas del empleado (pensión, pago): solo "desde"; valen hasta la siguiente y siguen al empleado entre contratos.
 */
class PlanillaLaboralConsulta
{
    // ------------------------------------------------------------------ fechas

    public static function fecha($fecha = null): Carbon
    {
        return ($fecha ? Carbon::parse($fecha) : now())->startOfDay();
    }

    /** Día que representa al mes para un contrato: fin de mes, o el fin del contrato si termina antes. */
    public static function fechaDelMes(int $mes, int $anio, ?PlanContrato $contrato = null): Carbon
    {
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->startOfDay();
        if ($contrato?->fecha_fin && $contrato->fecha_fin->lt($fin)) {
            return $contrato->fecha_fin->copy()->startOfDay();
        }
        return $fin;
    }

    /** "Hoy" dentro del contrato: hoy, o su inicio si aún no empieza, o su fin si ya terminó. */
    public static function fechaActual(PlanContrato $contrato): Carbon
    {
        $hoy = self::fecha();
        if ($contrato->fecha_inicio && $hoy->lt($contrato->fecha_inicio)) {
            return $contrato->fecha_inicio->copy()->startOfDay();
        }
        if ($contrato->fecha_fin && $hoy->gt($contrato->fecha_fin)) {
            return $contrato->fecha_fin->copy()->startOfDay();
        }
        return $hoy;
    }

    // ------------------------------------------------------------------ contratos

    public static function cubre(PlanContrato $contrato, $fecha): bool
    {
        $f = self::fecha($fecha);
        return $contrato->fecha_inicio->lte($f) && (!$contrato->fecha_fin || $contrato->fecha_fin->gte($f));
    }

    /** Contratos que cruzan el rango (incluye ambos extremos). Para filtros SQL. */
    public static function filtrarContratosEnRango($query, $desde, $hasta)
    {
        return $query->where('fecha_inicio', '<=', self::fecha($hasta)->toDateString())
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', self::fecha($desde)->toDateString()));
    }

    /**
     * Fechas con registro diario de planilla dentro del contrato agrario que dejarían de tener contrato agrario si el
     * contrato pasa a $nuevo (fecha_inicio, fecha_fin, tipo_planilla) o se elimina ($nuevo = null). Otro contrato agrario
     * del empleado que cubra la fecha la salva.
     *
     * @return string[] fechas Y-m-d ordenadas
     */
    public static function registrosDiariosSinContrato(PlanContrato $contrato, ?array $nuevo): array
    {
        $fechas = \App\Models\PlanRegistroDiario::query()
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'plan_registros_diarios.plan_det_men_id')
            ->where('d.plan_empleado_id', $contrato->plan_empleado_id)
            ->whereDate('plan_registros_diarios.fecha', '>=', $contrato->fecha_inicio)
            ->when($contrato->fecha_fin, fn($q) => $q->whereDate('plan_registros_diarios.fecha', '<=', $contrato->fecha_fin))
            ->orderBy('plan_registros_diarios.fecha')
            ->pluck('plan_registros_diarios.fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())->unique()->values();
        if ($fechas->isEmpty()) {
            return [];
        }

        $cubren = PlanContrato::where('plan_empleado_id', $contrato->plan_empleado_id)->where('id', '<>', $contrato->id)
            ->where('tipo_planilla', 'agraria')->get()
            ->map(fn($c) => [$c->fecha_inicio->toDateString(), $c->fecha_fin?->toDateString()]);
        if ($nuevo && ($nuevo['tipo_planilla'] ?? $contrato->tipo_planilla) === 'agraria') {
            $cubren->push([Carbon::parse($nuevo['fecha_inicio'])->toDateString(), blank($nuevo['fecha_fin'] ?? null) ? null : Carbon::parse($nuevo['fecha_fin'])->toDateString()]);
        }
        return $fechas->reject(fn($f) => $cubren->contains(fn($c) => $c[0] <= $f && ($c[1] === null || $c[1] >= $f)))->values()->all();
    }

    public static function contratoEn(PlanEmpleado $empleado, $fecha = null): ?PlanContrato
    {
        $f = self::fecha($fecha);
        return self::contratos($empleado)->first(fn(PlanContrato $c) => self::cubre($c, $f));
    }

    /** Contrato del mes: el último que empezó y cruza el mes. */
    public static function contratoDelMes(PlanEmpleado $empleado, int $mes, int $anio): ?PlanContrato
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fin = $inicio->copy()->endOfMonth();
        return self::contratos($empleado)->first(fn(PlanContrato $c) => $c->fecha_inicio->lte($fin) && (!$c->fecha_fin || $c->fecha_fin->gte($inicio)));
    }

    /** Estado calculado con las fechas: activo, en_prueba o finalizado (y pendiente si aún no empieza). */
    public static function estado(PlanContrato $contrato, $fecha = null): string
    {
        $f = self::fecha($fecha);
        if ($contrato->fecha_fin && $contrato->fecha_fin->lt($f)) {
            return 'finalizado';
        }
        if ($contrato->fecha_inicio->gt($f)) {
            return 'pendiente';
        }
        if ($contrato->fecha_fin_prueba && $contrato->fecha_fin_prueba->gte($f)) {
            return 'en_prueba';
        }
        return 'activo';
    }

    /** @return Collection<PlanContrato> del más reciente al más antiguo (usa la relación cargada si existe) */
    private static function contratos(PlanEmpleado $empleado): Collection
    {
        $lista = $empleado->relationLoaded('contratos') ? $empleado->contratos : $empleado->contratos()->get();
        return $lista->sortByDesc(fn($c) => $c->fecha_inicio->format('Ymd'))->values();
    }

    // ------------------------------------------------------------------ partículas

    /**
     * Regla de vigencia de una partícula: la de mayor "desde" que no pase la fecha. Si la fecha es anterior a todas
     * (dato incompleto), rige la primera.
     */
    public static function vigente(Collection $particulas, $fecha)
    {
        $f = self::fecha($fecha)->format('Ymd');
        $ordenadas = $particulas->sortBy(fn($p) => $p->desde->format('Ymd'))->values();
        return $ordenadas->filter(fn($p) => $p->desde->format('Ymd') <= $f)->last() ?? $ordenadas->first();
    }

    public static function asignacionEn(PlanContrato $contrato, $fecha = null): ?PlanContratoAsignacion
    {
        return self::vigente(self::relacion($contrato, 'asignaciones'), $fecha ?? self::fechaActual($contrato));
    }

    public static function remuneracionEn(PlanContrato $contrato, $fecha = null): ?PlanContratoRemuneracion
    {
        return self::vigente(self::relacion($contrato, 'remuneraciones'), $fecha ?? self::fechaActual($contrato));
    }

    public static function pensionEn(PlanEmpleado $empleado, $fecha = null): ?PlanEmpleadoPension
    {
        return self::vigente(self::relacion($empleado, 'pensiones'), $fecha ?? now());
    }

    public static function pagoEn(PlanEmpleado $empleado, $fecha = null): ?PlanEmpleadoPago
    {
        return self::vigente(self::relacion($empleado, 'pagos'), $fecha ?? now());
    }

    private static function relacion($modelo, string $relacion): Collection
    {
        return $modelo->relationLoaded($relacion) ? $modelo->{$relacion} : $modelo->{$relacion}()->get();
    }

    // ------------------------------------------------------------------ resumen del mes

    /**
     * Todo lo laboral de un empleado en un mes, en un solo arreglo (lo que usa la planilla mensual).
     * @return array{contrato: ?PlanContrato, fecha: ?Carbon, asignacion: ?PlanContratoAsignacion, remuneracion: ?PlanContratoRemuneracion, pension: ?PlanEmpleadoPension, pago: ?PlanEmpleadoPago}
     */
    public static function delMes(PlanEmpleado $empleado, int $mes, int $anio): array
    {
        $contrato = self::contratoDelMes($empleado, $mes, $anio);
        $fecha = $contrato ? self::fechaDelMes($mes, $anio, $contrato) : null;
        return [
            'contrato' => $contrato,
            'fecha' => $fecha,
            'asignacion' => $contrato ? self::asignacionEn($contrato, $fecha) : null,
            'remuneracion' => $contrato ? self::remuneracionEn($contrato, $fecha) : null,
            'pension' => $fecha ? self::pensionEn($empleado, $fecha) : self::pensionEn($empleado, Carbon::create($anio, $mes, 1)->endOfMonth()),
            'pago' => self::pagoEn($empleado, $fecha ?? Carbon::create($anio, $mes, 1)->endOfMonth()),
        ];
    }

    public static function sueldoDelMes(PlanEmpleado $empleado, int $mes, int $anio): ?float
    {
        $r = self::delMes($empleado, $mes, $anio)['remuneracion'];
        return $r && $r->sueldo !== null ? (float) $r->sueldo : null;
    }

    public static function grupoDelMes(PlanEmpleado $empleado, int $mes, int $anio): ?string
    {
        return self::delMes($empleado, $mes, $anio)['asignacion']?->grupo_codigo;
    }

    /** Relaciones a precargar para leer el historial sin consultas por fila. */
    public static function precarga(): array
    {
        return ['persona', 'contratos.asignaciones.grupo', 'contratos.asignaciones.cargo', 'contratos.remuneraciones', 'pensiones.descuento', 'pagos'];
    }

    // ------------------------------------------------------------------ cargos

    /**
     * Empleados que ocupan cada cargo en la fecha (su asignación vigente dentro de un contrato vigente).
     * @return array<int, int[]> cargo_id => ids de empleados
     */
    public static function ocupantesPorCargo($fecha = null): array
    {
        $f = self::fecha($fecha);
        $r = [];
        $contratos = PlanContrato::with('asignaciones')->whereHas('empleado')->vigentes($f)->get();
        foreach ($contratos as $c) {
            $cargo = self::asignacionEn($c, $f)?->plan_cargo_id;
            if ($cargo) {
                $r[$cargo][] = $c->plan_empleado_id;
            }
        }
        return $r;
    }

    // ------------------------------------------------------------------ filtros SQL

    /** Empleados cuyo sistema de pensión vigente en la fecha es $codigo. */
    public static function filtrarEmpleadosPorPension(Builder $empleados, string $codigo, $fecha = null): Builder
    {
        $f = self::fecha($fecha)->toDateString();
        return $empleados->whereHas('pensiones', function ($p) use ($codigo, $f) {
            $p->where('plan_sp_codigo', $codigo)->where('desde', function ($sub) use ($f) {
                $sub->selectRaw('MAX(x.desde)')->from('plan_empleado_pensiones as x')
                    ->whereColumn('x.plan_empleado_id', 'plan_empleado_pensiones.plan_empleado_id')->where('x.desde', '<=', $f);
            });
        });
    }

    /** Empleados cuyo cargo vigente en la fecha es $cargoId. */
    public static function filtrarEmpleadosPorCargo(Builder $empleados, int $cargoId, $fecha = null): Builder
    {
        return $empleados->whereIn('plan_empleados.id', self::ocupantesPorCargo($fecha)[$cargoId] ?? [0]);
    }

    /**
     * Empleados cuyo grupo (asignación vigente) en la fecha es $grupo ("*" = cualquier grupo). La vigencia se
     * resuelve en SQL: la asignación de mayor "desde" <= fecha dentro de un contrato que cubre la fecha.
     */
    public static function filtrarEmpleadosPorGrupo(Builder $empleados, string $grupo, $fecha): Builder
    {
        $f = self::fecha($fecha)->toDateString();
        return $empleados->whereHas('contratos', function ($c) use ($grupo, $f) {
            self::filtrarContratosEnRango($c, $f, $f)->whereHas('asignaciones', function ($a) use ($grupo, $f) {
                $a->where('desde', function ($sub) use ($f) {
                    $sub->selectRaw('MAX(x.desde)')->from('plan_contrato_asignaciones as x')
                        ->whereColumn('x.plan_contrato_id', 'plan_contrato_asignaciones.plan_contrato_id')->where('x.desde', '<=', $f);
                });
                $grupo === '*' ? $a->whereNotNull('grupo_codigo') : $a->where('grupo_codigo', $grupo);
            });
        });
    }
}
