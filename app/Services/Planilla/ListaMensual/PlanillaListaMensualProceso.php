<?php

namespace App\Services\Planilla\ListaMensual;

use App\Exceptions\ErrorControlado;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Planilla\Suspension\PlanillaSuspensionDiarioProceso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lista mensual del registro diario de planilla (plan_mensual_detalles): quién aparece cada mes y en qué orden.
 *
 * - Un mes sin lista se genera solo al abrir el registro diario (generar), copiando el orden del último mes con lista.
 * - Al guardar, finalizar, reabrir o eliminar un contrato agrario, el empleado se agrega al final (o se quita) de los
 *   meses que ya tienen lista (sincronizarEmpleado). Nadie tiene que "actualizar contratados".
 * - "Gestionar lista" solo cambia el orden (guardarOrden).
 * Quién va en un mes: empleado activo con contrato de planilla agraria que cruza el mes (enMes).
 */
class PlanillaListaMensualProceso
{
    /** ¿El mes ya tiene lista? (puede existir plan_mensuales sin empleados: la crean otros módulos) */
    public static function existe(int $mes, int $anio): bool
    {
        return PlanMensualDetalle::whereHas('planillaMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))->exists();
    }

    /** ¿El empleado debe ir en la lista del mes? */
    public static function enMes(PlanEmpleado $empleado, int $mes, int $anio): bool
    {
        if ($empleado->trashed()) {
            return false;
        }
        [$inicio, $fin] = self::rango($mes, $anio);
        return $empleado->contratos()->where('tipo_planilla', 'agraria')->enRango($inicio, $fin)->exists();
    }

    /**
     * Crea la lista del mes si no existe: los contratados del mes con el orden del último mes con lista (los nuevos al
     * final) y sus vacaciones / permisos ya programados en el registro diario.
     *
     * @return int empleados agregados (0 si ya existía o no hay contratados)
     */
    public function generar(int $mes, int $anio): int
    {
        if (self::existe($mes, $anio)) {
            return 0;
        }
        $empleados = app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($mes, $anio);
        if ($empleados->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($mes, $anio, $empleados) {
            $plan = PlanMensual::firstOrCreate(['mes' => $mes, 'anio' => $anio]);
            $orden = PlanillaListaMensualReglas::ordenar($empleados->pluck('id')->all(), $this->ordenAnterior($mes, $anio));
            [$inicio, $fin] = self::rango($mes, $anio);
            foreach ($empleados as $e) {
                PlanMensualDetalle::create(['plan_mensual_id' => $plan->id, 'plan_empleado_id' => $e->id,
                    'nombres' => $e->nombre_completo, 'documento' => $e->documento, 'orden' => $orden[$e->id]]);
            }
            foreach ($empleados as $e) {
                app(PlanillaSuspensionDiarioProceso::class)->reconciliar($e->id, $inicio->toDateString(), $fin->toDateString());
            }
            return $empleados->count();
        });
    }

    /**
     * Pone o quita al empleado en los meses que ya tienen lista, según sus contratos de hoy. Se llama después de cada
     * cambio de contrato. Entra al final; al salir, los de abajo suben (sin huecos). No quita a quien ya tiene registros
     * diarios ese mes (los contratos no dejan llegar a ese caso: ver PlanillaLaboralProceso).
     *
     * @return array{agregado: string[], quitado: string[]} meses "m/aaaa"
     */
    public function sincronizarEmpleado(PlanEmpleado $empleado): array
    {
        $r = ['agregado' => [], 'quitado' => []];
        $meses = PlanMensual::whereHas('detalle')->orderBy('anio')->orderBy('mes')->get();
        foreach ($meses as $plan) {
            $detalle = PlanMensualDetalle::where('plan_mensual_id', $plan->id)->where('plan_empleado_id', $empleado->id)->first();
            $debe = self::enMes($empleado, (int) $plan->mes, (int) $plan->anio);
            if ($debe && !$detalle) {
                PlanMensualDetalle::create(['plan_mensual_id' => $plan->id, 'plan_empleado_id' => $empleado->id,
                    'nombres' => $empleado->nombre_completo, 'documento' => $empleado->documento,
                    'orden' => (int) PlanMensualDetalle::where('plan_mensual_id', $plan->id)->max('orden') + 1]);
                [$inicio, $fin] = self::rango((int) $plan->mes, (int) $plan->anio);
                app(PlanillaSuspensionDiarioProceso::class)->reconciliar($empleado->id, $inicio->toDateString(), $fin->toDateString());
                $r['agregado'][] = "{$plan->mes}/{$plan->anio}";
            } elseif (!$debe && $detalle && !$detalle->registrosDiarios()->exists()) {
                $detalle->delete();
                $this->compactar($plan->id);
                $r['quitado'][] = "{$plan->mes}/{$plan->anio}";
            }
        }
        return $r;
    }

    /** Fila del empleado en el mes; si no está, entra al final de la lista (para quien calcula la planilla). */
    public function asegurarEmpleado(PlanMensual $plan, PlanEmpleado $empleado): PlanMensualDetalle
    {
        return PlanMensualDetalle::where('plan_mensual_id', $plan->id)->where('plan_empleado_id', $empleado->id)->first()
            ?? PlanMensualDetalle::create(['plan_mensual_id' => $plan->id, 'plan_empleado_id' => $empleado->id,
                'nombres' => $empleado->nombre_completo, 'documento' => $empleado->documento,
                'orden' => (int) PlanMensualDetalle::where('plan_mensual_id', $plan->id)->max('orden') + 1]);
    }

    /**
     * Guarda el orden de "Gestionar lista". Solo ordena a los que ya están: no agrega ni quita a nadie.
     *
     * @param array<int, array{id: int, orden: int}> $lista id = plan_empleado_id
     */
    public function guardarOrden(int $mes, int $anio, array $lista): void
    {
        $plan = PlanMensual::where('mes', $mes)->where('anio', $anio)->first();
        if (!$plan) {
            throw new ErrorControlado("El mes {$mes}/{$anio} todavía no tiene lista.");
        }
        DB::transaction(function () use ($plan, $lista) {
            $orden = collect($lista)->mapWithKeys(fn($e) => [(int) $e['id'] => (int) $e['orden']])->all();
            $detalles = PlanMensualDetalle::where('plan_mensual_id', $plan->id)->get(['id', 'plan_empleado_id', 'orden']);
            // Los que no vinieron (se agregaron mientras el modal estaba abierto) quedan al final
            $tope = $orden ? max($orden) : 0;
            $nuevo = [];
            foreach ($detalles as $d) {
                $nuevo[$d->id] = $orden[$d->plan_empleado_id] ?? ++$tope;
            }
            foreach (PlanillaListaMensualReglas::compactar($nuevo) as $id => $n) {
                PlanMensualDetalle::whereKey($id)->update(['orden' => $n]);
            }
        });
    }

    /** Renumera la lista 1..n sin cambiar el orden. */
    public function compactar(int $planMensualId): void
    {
        $actual = PlanMensualDetalle::where('plan_mensual_id', $planMensualId)->pluck('orden', 'id')->all();
        foreach (PlanillaListaMensualReglas::compactar($actual) as $id => $n) {
            if ((int) $actual[$id] !== $n) {
                PlanMensualDetalle::whereKey($id)->update(['orden' => $n]);
            }
        }
    }

    /** @return array<int, int> plan_empleado_id => orden del último mes con lista antes de este */
    private function ordenAnterior(int $mes, int $anio): array
    {
        $plan = PlanMensual::whereHas('detalle')
            ->whereRaw('anio * 12 + mes < ?', [$anio * 12 + $mes])
            ->orderByDesc('anio')->orderByDesc('mes')->first();
        return $plan ? PlanMensualDetalle::where('plan_mensual_id', $plan->id)->whereNotNull('plan_empleado_id')
            ->pluck('orden', 'plan_empleado_id')->map(fn($o) => (int) $o)->all() : [];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private static function rango(int $mes, int $anio): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        return [$inicio, $inicio->copy()->endOfMonth()->startOfDay()];
    }
}
