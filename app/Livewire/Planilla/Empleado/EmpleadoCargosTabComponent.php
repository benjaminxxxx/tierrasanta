<?php

namespace App\Livewire\Planilla\Empleado;

use App\Models\PlanCargo;
use App\Services\Planilla\EmpleadoCargoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pestaña "Cargos" del panel del empleado (reemplaza al modal EmpleadoCargoComponent).
 */
class EmpleadoCargosTabComponent extends Component
{
    use LivewireAlert;

    public int $empleadoId;

    public ?int $planCargoId = null;
    public ?string $mesInicio = null;
    public ?string $grupoCodigo = null;
    public string $motivoCambio = 'ingreso';
    public ?string $mesFin = null;

    private function servicio(): EmpleadoCargoServicio
    {
        return new EmpleadoCargoServicio($this->empleadoId);
    }

    public function asignarCargo(): void
    {
        $this->validate([
            'planCargoId' => ['required', 'integer', 'exists:plan_cargos,id'],
            'mesInicio' => ['required', 'date_format:Y-m'],
            'grupoCodigo' => ['nullable', 'string', 'max:50'],
            'motivoCambio' => ['required', 'string'],
        ]);

        try {
            $this->servicio()->asignarCargo(
                planCargoId: $this->planCargoId,
                mesInicio: $this->mesInicio,
                grupoCodigo: $this->grupoCodigo,
                motivo: $this->motivoCambio,
            );
            $this->alert('success', 'Cargo asignado correctamente.');
            $this->limpiar();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function finalizarCargoActual(): void
    {
        $this->validate(['mesFin' => ['required', 'date_format:Y-m']]);

        try {
            $this->servicio()->finalizarCargo($this->mesFin);
            $this->alert('success', 'Cargo finalizado correctamente.');
            $this->limpiar();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function reabrirCargo(int $planContratoCargoId): void
    {
        try {
            $this->servicio()->reabrirCargo($planContratoCargoId);
            $this->alert('success', 'Cargo reaperturado correctamente.');
            $this->limpiar();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function eliminarCargoAbierto(): void
    {
        try {
            $this->servicio()->eliminarCargoAbierto();
            $this->alert('success', 'Registro de cargo eliminado.');
            $this->limpiar();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    private function limpiar(): void
    {
        $this->reset(['planCargoId', 'mesInicio', 'grupoCodigo', 'mesFin']);
        $this->motivoCambio = 'ingreso';
        $this->resetErrorBag();
        $this->dispatch('cargoAsignado');      // refresca la lista de empleados
        $this->dispatch('empleadoActualizado'); // refresca el perfil
    }

    public function render()
    {
        return view('livewire.planilla.empleado.empleado-cargos-tab-component', [
            'historial' => $this->servicio()->historial(),
            'cargoVigente' => $this->servicio()->cargoVigente(),
            'cargos' => PlanCargo::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
