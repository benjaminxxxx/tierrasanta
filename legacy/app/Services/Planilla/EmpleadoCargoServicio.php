<?php

namespace App\Services\Planilla;

use App\Models\PlanCargo;
use App\Models\PlanEmpleadoCargo;
use App\Models\PlanEmpleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmpleadoCargoServicio
{
    protected PlanEmpleado $empleado;

    public function __construct(int $empleadoId)
    {
        $this->empleado = PlanEmpleado::findOrFail($empleadoId);
    }

    public function cargoVigente(): ?PlanEmpleadoCargo
    {
        return PlanEmpleadoCargo::with('cargo')
            ->where('plan_empleado_id', $this->empleado->id)
            ->whereNull('fecha_fin')
            ->first();
    }

    public function historial()
    {
        return PlanEmpleadoCargo::with('cargo')
            ->where('plan_empleado_id', $this->empleado->id)
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public function asignarCargo(int $planCargoId, string $mesInicio, ?string $grupoCodigo = null, string $motivo = 'asignacion'): PlanEmpleadoCargo
    {
        if ($this->cargoVigente()) {
            throw new \DomainException('El empleado tiene un cargo vigente. Debe finalizarlo antes de asignar uno nuevo.');
        }

        $fechaInicio = Carbon::parse($mesInicio . '-01')->startOfMonth();

        // Regla que faltaba: no se puede iniciar un cargo dentro (o antes) del rango de uno ya cerrado
        $ultimoCerrado = PlanEmpleadoCargo::where('plan_empleado_id', $this->empleado->id)
            ->orderByDesc('fecha_fin')
            ->first();

        if ($ultimoCerrado && $fechaInicio->lte($ultimoCerrado->fecha_fin)) {
            throw new \DomainException(
                "La fecha de inicio debe ser posterior a {$ultimoCerrado->fecha_fin->format('m/Y')}, "
                . "que es cuando finalizó el cargo anterior ({$ultimoCerrado->cargo->nombre})."
            );
        }

        $fechaInicio_ok = $fechaInicio; // renombro solo para claridad en el diff

        return DB::transaction(function () use ($planCargoId, $fechaInicio_ok, $grupoCodigo, $motivo) {
            $cargo = PlanCargo::lockForUpdate()->findOrFail($planCargoId);

            if ($cargo->cupo_maximo !== null) {
                $ocupante = PlanEmpleadoCargo::with('empleado')
                    ->where('plan_cargo_id', $cargo->id)
                    ->whereNull('fecha_fin')
                    ->lockForUpdate()
                    ->first();

                if ($ocupante) {
                    $nombre = trim($ocupante->empleado->nombres . ' ' . $ocupante->empleado->apellido_paterno);
                    throw new \DomainException("El cargo tiene una sola plaza y esta aún vigente para el empleado {$nombre}.");
                }
            }

            return PlanEmpleadoCargo::create([
                'plan_empleado_id' => $this->empleado->id,
                'plan_cargo_id' => $cargo->id,
                'grupo_codigo' => $grupoCodigo,
                'fecha_inicio' => $fechaInicio_ok,
                'motivo_cambio' => $motivo,
                'creado_por' => auth()->user()?->name,
            ]);
        });
    }

    public function finalizarCargo(string $mesFin): PlanEmpleadoCargo
    {
        $vigente = $this->cargoVigente();

        if (!$vigente) {
            throw new \DomainException('El empleado no tiene un cargo vigente para finalizar.');
        }

        $fechaFin = Carbon::parse($mesFin . '-01')->endOfMonth();

        if ($fechaFin->lt($vigente->fecha_inicio)) {
            throw new \DomainException('El mes de fin no puede ser anterior al mes de inicio del cargo vigente.');
        }

        if ($fechaFin->gt(now()->endOfMonth())) {
            throw new \DomainException('No se puede finalizar un cargo con una fecha posterior al mes actual.');
        }

        $vigente->update(['fecha_fin' => $fechaFin]);

        return $vigente->fresh();
    }

    public function reabrirCargo(int $PlanEmpleadoCargoId): PlanEmpleadoCargo
    {
        $registro = PlanEmpleadoCargo::where('plan_empleado_id', $this->empleado->id)
            ->findOrFail($PlanEmpleadoCargoId);

        if (is_null($registro->fecha_fin)) {
            throw new \DomainException('Este cargo ya está vigente.');
        }

        $masReciente = PlanEmpleadoCargo::where('plan_empleado_id', $this->empleado->id)
            ->orderByDesc('fecha_inicio')
            ->first();

        if ($masReciente->id !== $registro->id) {
            throw new \DomainException('Solo se puede reaperturar el registro más reciente del historial.');
        }

        $registro->update(['fecha_fin' => null]);

        return $registro->fresh();
    }

    public function eliminarCargoAbierto(): void
    {
        $vigente = $this->cargoVigente();

        if (!$vigente) {
            throw new \DomainException('No hay un cargo vigente (abierto) para eliminar.');
        }

        $vigente->delete();
    }
}