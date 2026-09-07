<?php

namespace App\Services\Planilla;

use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use Illuminate\Support\Carbon;

class SincronizarSuspensionDesdeAsistenciaServicio
{
    /**
     * Se llama cada vez que el registro diario de un empleado cambia de código
     * de asistencia. Si el código nuevo tiene un tipo de suspensión asociado,
     * intenta extender una suspensión adyacente existente o crea una nueva
     * de un solo día. Si el código no tiene mapeo, no hace nada (requiere
     * vinculación manual desde el admin de tipos de asistencia).
     */
    public function sincronizar(int $planEmpleadoId, string $fecha, ?string $codigoAsistenciaAnterior, ?string $codigoAsistenciaNuevo): void
    {
        // 1. Si el código anterior tenía suspensión asociada y cambió a otro
        // código (o se borró), hay que retirar ese día de su suspensión.
        if ($codigoAsistenciaAnterior && $codigoAsistenciaAnterior !== $codigoAsistenciaNuevo) {
            $this->retirarDiaDeSuspension($planEmpleadoId, $fecha, $codigoAsistenciaAnterior);
        }

        if (!$codigoAsistenciaNuevo) {
            return;
        }

        $tipoAsistencia = PlanTipoAsistencia::where('codigo', $codigoAsistenciaNuevo)->first();

        if (!$tipoAsistencia || !$tipoAsistencia->plan_tipo_suspension_id) {
            return; // sin mapeo -> requiere vinculación manual, no se toca nada
        }

        $this->agregarOExtender($planEmpleadoId, $fecha, $tipoAsistencia->plan_tipo_suspension_id);
    }

    private function agregarOExtender(int $planEmpleadoId, string $fecha, int $tipoSuspensionId): void
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diaAnterior = $fechaCarbon->copy()->subDay()->toDateString();
        $diaSiguiente = $fechaCarbon->copy()->addDay()->toDateString();

        // ¿Ya existe una suspensión de este mismo tipo que ya incluye este día?
        $existente = PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
            ->where('tipo_suspension_id', $tipoSuspensionId)
            ->where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->where('fecha_fin', '>=', $fecha)->orWhereNull('fecha_fin');
            })
            ->first();

        if ($existente) {
            return; // ya cubierto, nada que hacer
        }

        // Buscar suspensión adyacente (termina el día anterior)
        $adyacenteAntes = PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
            ->where('tipo_suspension_id', $tipoSuspensionId)
            ->where('fecha_fin', $diaAnterior)
            ->first();

        // Buscar suspensión adyacente (empieza el día siguiente)
        $adyacenteDespues = PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
            ->where('tipo_suspension_id', $tipoSuspensionId)
            ->where('fecha_inicio', $diaSiguiente)
            ->first();

        if ($adyacenteAntes && $adyacenteDespues) {
            // El nuevo día conecta dos tramos existentes -> fusionar en uno solo
            $adyacenteAntes->update(['fecha_fin' => $adyacenteDespues->fecha_fin]);
            $adyacenteDespues->delete();
            return;
        }

        if ($adyacenteAntes) {
            $adyacenteAntes->update(['fecha_fin' => $fecha]);
            return;
        }

        if ($adyacenteDespues) {
            $adyacenteDespues->update(['fecha_inicio' => $fecha]);
            return;
        }

        // No hay nada adyacente -> registro independiente de un solo día
        PlanSuspension::create([
            'plan_empleado_id' => $planEmpleadoId,
            'tipo_suspension_id' => $tipoSuspensionId,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha,
        ]);
    }

    private function retirarDiaDeSuspension(int $planEmpleadoId, string $fecha, string $codigoAnterior): void
    {
        $tipoAnterior = PlanTipoAsistencia::where('codigo', $codigoAnterior)->first();

        if (!$tipoAnterior || !$tipoAnterior->plan_tipo_suspension_id) {
            return;
        }

        $suspension = PlanSuspension::where('plan_empleado_id', $planEmpleadoId)
            ->where('tipo_suspension_id', $tipoAnterior->plan_tipo_suspension_id)
            ->where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->where('fecha_fin', '>=', $fecha)->orWhereNull('fecha_fin');
            })
            ->first();

        if (!$suspension) {
            return;
        }

        $esInicio = $suspension->fecha_inicio === $fecha;
        $esFin = $suspension->fecha_fin === $fecha;

        if ($esInicio && $esFin) {
            $suspension->delete();
        } elseif ($esInicio) {
            $suspension->update(['fecha_inicio' => Carbon::parse($fecha)->addDay()->toDateString()]);
        } elseif ($esFin) {
            $suspension->update(['fecha_fin' => Carbon::parse($fecha)->subDay()->toDateString()]);
        } else {
            // Día en medio del rango -> partir en dos: [inicio, día-1] y [día+1, fin]
            $finOriginal = $suspension->fecha_fin;

            $suspension->update([
                'fecha_fin' => Carbon::parse($fecha)->subDay()->toDateString(),
            ]);

            PlanSuspension::create([
                'plan_empleado_id' => $planEmpleadoId,
                'tipo_suspension_id' => $tipoAnterior->plan_tipo_suspension_id,
                'fecha_inicio' => Carbon::parse($fecha)->addDay()->toDateString(),
                'fecha_fin' => $finOriginal,
            ]);
        }
    }
}