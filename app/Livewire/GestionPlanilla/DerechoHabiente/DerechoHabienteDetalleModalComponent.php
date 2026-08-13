<?php

namespace App\Livewire\GestionPlanilla\DerechoHabiente;

use Livewire\Component;
use App\Models\PlanEmpleado;
use App\Domain\DerechoHabiente\EmpleadoAsignacionFamiliarService;

class DerechoHabienteDetalleModalComponent extends Component
{
    public bool $show = false;
    public ?int $empleadoId = null;
    public string $empleadoNombre = '';
    public bool $tieneAsignacion = false;
    public array $detalle = [];

    protected $listeners = ['abrirDerechoHabienteDetalle' => 'abrir'];

    public function abrir(int $empleadoId)
    {
        $empleado = PlanEmpleado::findOrFail($empleadoId);
        $resultado = app(EmpleadoAsignacionFamiliarService::class)
            ->explicarEmpleado($empleadoId, now()->month, now()->year);

        $this->empleadoId = $empleadoId;
        $this->empleadoNombre = $empleado->nombreCompleto;
        $this->tieneAsignacion = $resultado['tiene'];
        $this->detalle = $resultado['detalle'];
        $this->show = true;
    }

    public function cerrar()
    {
        $this->reset(['show', 'empleadoId', 'empleadoNombre', 'tieneAsignacion', 'detalle']);
    }

    public function render()
    {
        return view('livewire.gestion-planilla.derecho-habiente.derecho-habiente-detalle-modal-component');
    }
}