<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanCargo;
use App\Models\PlanEmpleado;
use App\Services\Planilla\EmpleadoCargoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EmpleadoCargoComponent extends Component
{
    use LivewireAlert;

    public bool $mostrarModal = false;

    public ?int $empleadoId = null;
    public string $empleadoNombre = '';

    public $historial = [];
    public $cargoVigente = null;

    // Form: asignar cargo nuevo
    public ?int $planCargoId = null;
    public ?string $mesInicio = null;
    public ?string $grupoCodigo = null;
    public string $motivoCambio = 'ingreso';

    // Form: finalizar cargo vigente
    public ?string $mesFin = null;

    protected $listeners = ['abrirFormularioRegistroEmpleadoCargo' => 'abrirParaEmpleado'];

    public function abrirParaEmpleado($id): void
    {
        $empleado = PlanEmpleado::find($id);

        if (! $empleado) {
            $this->alert('error', 'Empleado inexistente.');
            return;
        }

        $this->empleadoId = $empleado->id;
        $this->empleadoNombre = trim("{$empleado->nombres} {$empleado->apellido_paterno} {$empleado->apellido_materno}");

        $this->resetFormularios();
        $this->cargarHistorial();
        $this->mostrarModal = true;
    }

    protected function resetFormularios(): void
    {
        $this->reset(['planCargoId', 'mesInicio', 'grupoCodigo', 'motivoCambio', 'mesFin']);
        $this->motivoCambio = 'ingreso';
        $this->resetErrorBag();
    }

    protected function cargarHistorial(): void
    {
        $servicio = new EmpleadoCargoServicio($this->empleadoId);
        $this->historial = $servicio->historial();
        $this->cargoVigente = $servicio->cargoVigente();
    }

    protected function rulesAsignar(): array
    {
        return [
            'planCargoId' => ['required', 'integer', 'exists:plan_cargos,id'],
            'mesInicio' => ['required', 'date_format:Y-m'],
            'grupoCodigo' => ['nullable', 'string', 'max:50'],
            'motivoCambio' => ['required', 'string'],
        ];
    }

    public function asignarCargo(): void
    {
        $this->validate($this->rulesAsignar());

        try {
            (new EmpleadoCargoServicio($this->empleadoId))->asignarCargo(
                planCargoId: $this->planCargoId,
                mesInicio: $this->mesInicio,
                grupoCodigo: $this->grupoCodigo,
                motivo: $this->motivoCambio,
            );

            $this->alert('success', 'Cargo asignado correctamente.');
            $this->resetFormularios();
            $this->cargarHistorial();
            $this->dispatch('cargoAsignado');
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function finalizarCargoActual(): void
    {
        $this->validate(['mesFin' => ['required', 'date_format:Y-m']]);

        try {
            (new EmpleadoCargoServicio($this->empleadoId))->finalizarCargo($this->mesFin);

            $this->alert('success', 'Cargo finalizado correctamente.');
            $this->resetFormularios();
            $this->cargarHistorial();            
            $this->dispatch('cargoAsignado');
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function reabrirCargo(int $planContratoCargoId): void
    {
        try {
            (new EmpleadoCargoServicio($this->empleadoId))->reabrirCargo($planContratoCargoId);

            $this->alert('success', 'Cargo reaperturado correctamente.');
            $this->cargarHistorial();
            $this->dispatch('cargoAsignado');
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function eliminarCargoAbierto(): void
    {
        try {
            (new EmpleadoCargoServicio($this->empleadoId))->eliminarCargoAbierto();

            $this->alert('success', 'Registro de cargo eliminado.');
            $this->cargarHistorial();
            $this->dispatch('cargoAsignado');
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function render()
    {
        $cargos = PlanCargo::where('activo', true)->orderBy('nombre')->get();

        return view('livewire.gestion-planilla.empleado-cargo-component', compact('cargos'));
    }
}