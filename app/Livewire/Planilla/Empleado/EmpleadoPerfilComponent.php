<?php

namespace App\Livewire\Planilla\Empleado;

use App\Services\Planilla\Empleado\EmpleadoPerfilServicio;
use Livewire\Component;

/**
 * Perfil del trabajador: estado actual, evolución del sueldo y línea de tiempo cronológica
 * (ingresos, ceses, reingresos, cargos, sueldos, suspensiones, derecho habientes).
 */
class EmpleadoPerfilComponent extends Component
{
    public int $empleadoId;

    // Las otras pestañas avisan cuando cambian contratos, sueldos o cargos
    protected $listeners = ['empleadoActualizado' => '$refresh'];

    public function render()
    {
        return view('livewire.planilla.empleado.empleado-perfil-component', [
            'perfil' => app(EmpleadoPerfilServicio::class)->construir($this->empleadoId),
        ]);
    }
}
