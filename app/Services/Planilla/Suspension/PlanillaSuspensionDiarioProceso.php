<?php

namespace App\Services\Planilla\Suspension;

use App\Exceptions\ErrorControlado;
use App\Models\PlanContrato;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioServicio;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene el registro diario de acuerdo con las suspensiones (vacaciones, descanso médico, licencias…).
 *
 * Regla: un día con suspensión lleva en el registro diario el código de asistencia ligado a ese tipo (V, DM, LSG…), y un
 * día sin suspensión no lleva esos códigos. Así:
 *  - al programar vacaciones del 15 al 28, esos días aparecen solos con V (si el mes ya tiene lista);
 *  - si regresa antes y se acorta el rango, las V que sobran se quitan (se revierten);
 *  - un día ya registrado con otra cosa (A con labores, F…) no se pisa: queda como conflicto para revisar.
 * No toca domingos, días fuera de contrato agrario, días pagados ni registros con labores.
 */
class PlanillaSuspensionDiarioProceso
{
    /**
     * @return array{registrados: int, revertidos: int, conflictos: string[]}
     */
    public function reconciliar(int $planEmpleadoId, string $desde, string $hasta): array
    {
        $r = ['registrados' => 0, 'revertidos' => 0, 'conflictos' => []];
        $inicio = Carbon::parse($desde)->startOfDay();
        $fin = Carbon::parse($hasta)->startOfDay();
        if ($fin->lt($inicio)) {
            return $r;
        }

        [$codigoDeTipo, $tipoDeCodigo, $horas] = $this->mapaCodigos();
        $suspensiones = PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
            ->whereDate('fecha_inicio', '<=', $fin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))
            ->get();
        $contratos = PlanContrato::where('plan_empleado_id', $planEmpleadoId)->where('tipo_planilla', 'agraria')->enRango($inicio, $fin)->get();
        $detalles = PlanMensualDetalle::with('planillaMensual')->where('plan_empleado_id', $planEmpleadoId)
            ->whereHas('planillaMensual', fn($q) => $q->whereRaw('anio * 12 + mes between ? and ?', [$inicio->year * 12 + $inicio->month, $fin->year * 12 + $fin->month]))
            ->get()->keyBy(fn($d) => $d->planillaMensual->anio . '-' . (int) $d->planillaMensual->mes);
        if ($detalles->isEmpty()) {
            return $r; // el mes todavía no tiene lista: se hará al generarla
        }
        $registros = PlanRegistroDiario::withCount('detalles')->whereIn('plan_det_men_id', $detalles->pluck('id'))
            ->whereDate('fecha', '>=', $inicio)->whereDate('fecha', '<=', $fin)->get()
            ->keyBy(fn($x) => Carbon::parse($x->fecha)->toDateString());

        $fechas = [];
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $fecha = $d->toDateString();
            $detalle = $detalles->get($d->year . '-' . $d->month);
            if ($d->isSunday() || !$detalle) {
                continue;
            }
            $s = $suspensiones->first(fn($s) => $s->fecha_inicio->lte($d) && (!$s->fecha_fin || $s->fecha_fin->gte($d)));
            $registro = $registros->get($fecha);
            $libre = $registro && !$registro->detalles_count && !$registro->esta_pagado; // sin labores ni pago: se puede cambiar
            $deSuspension = $registro && isset($tipoDeCodigo[$registro->asistencia]);

            if ($s) {
                $codigo = $codigoDeTipo[$s->tipo_suspension_id] ?? null;
                $enContrato = $contratos->contains(fn($c) => $c->fecha_inicio->lte($d) && (!$c->fecha_fin || $c->fecha_fin->gte($d)));
                if (!$codigo || !$enContrato || ($deSuspension && $tipoDeCodigo[$registro->asistencia] === $s->tipo_suspension_id)) {
                    continue;
                }
                if (!$registro) {
                    PlanRegistroDiario::create(['plan_det_men_id' => $detalle->id, 'fecha' => $fecha, 'asistencia' => $codigo, 'total_horas' => $horas[$codigo]]);
                } elseif ($libre && ($deSuspension || $registro->asistencia === '')) {
                    $registro->update(['asistencia' => $codigo, 'total_horas' => $horas[$codigo]]);
                } else {
                    $r['conflictos'][] = $d->format('d/m/Y') . " registrado como {$registro->asistencia} (la suspensión pide {$codigo})";
                    continue;
                }
                $r['registrados']++;
                $fechas[$fecha] = true;
            } elseif ($deSuspension && $libre) {
                $registro->delete();
                $r['revertidos']++;
                $fechas[$fecha] = true;
            }
        }

        $servicio = app(PlanillaRegistroDiarioServicio::class);
        foreach (array_keys($fechas) as $fecha) {
            $servicio->actualizarResumenAsistencia($fecha);
        }
        if ($fechas) {
            \App\Services\Costos\Consolidacion\BddManoObraServicio::registrarCambio(min(array_keys($fechas)), max(array_keys($fechas)));
        }
        return $r;
    }

    /**
     * Regresó antes: la suspensión termina el día anterior al regreso y los días que sobran se quitan del registro diario
     * (quedan vacíos para registrar lo que hizo). Si regresó el mismo día que empezaba, la suspensión se elimina.
     *
     * @return array{registrados: int, revertidos: int, conflictos: string[]}
     */
    public function regresoAnticipado(int $suspensionId, string $fechaRegreso): array
    {
        $s = PlanSuspension::findOrFail($suspensionId);
        $regreso = Carbon::parse($fechaRegreso)->startOfDay();
        if ($regreso->lt($s->fecha_inicio) || ($s->fecha_fin && $regreso->gt($s->fecha_fin))) {
            throw new ErrorControlado('La fecha de regreso debe estar dentro de la suspensión (' . $s->fecha_inicio->format('d/m/Y')
                . ($s->fecha_fin ? ' al ' . $s->fecha_fin->format('d/m/Y') : '') . ').');
        }
        $finAnterior = ($s->fecha_fin ?? $regreso)->toDateString();

        return DB::transaction(function () use ($s, $regreso, $finAnterior) {
            $antes = $s->only(['tipo_suspension_id', 'fecha_inicio', 'fecha_fin']);
            if ($regreso->lte($s->fecha_inicio)) {
                AuditoriaServicio::registrar(PlanSuspension::class, $s->id, 'eliminar', $antes, null, 'Regresó el primer día: suspensión eliminada');
                $s->delete();
            } else {
                $nuevoFin = $regreso->copy()->subDay()->toDateString();
                $s->update(['fecha_fin' => $nuevoFin, 'actualizado_por' => Auth::id()]);
                AuditoriaServicio::registrar(PlanSuspension::class, $s->id, 'editar', $antes, ['fecha_fin' => $nuevoFin], 'Regresó antes: ' . $regreso->format('d/m/Y'));
            }
            return $this->reconciliar($s->plan_empleado_id, $regreso->toDateString(), $finAnterior);
        });
    }

    /**
     * @return array{0: array<int, string>, 1: array<string, int>, 2: array<string, float>}
     *   tipo_suspension_id => código de asistencia (si dos códigos usan el mismo tipo, el primero creado),
     *   código => tipo_suspension_id, código => horas del jornal
     */
    private function mapaCodigos(): array
    {
        $codigoDeTipo = $tipoDeCodigo = $horas = [];
        foreach (PlanTipoAsistencia::whereNotNull('plan_tipo_suspension_id')->orderBy('id')->get() as $t) {
            $codigoDeTipo[$t->plan_tipo_suspension_id] ??= $t->codigo;
            $tipoDeCodigo[$t->codigo] = (int) $t->plan_tipo_suspension_id;
            $horas[$t->codigo] = is_numeric($t->horas_jornal) ? (float) $t->horas_jornal : 0;
        }
        return [$codigoDeTipo, $tipoDeCodigo, $horas];
    }
}
