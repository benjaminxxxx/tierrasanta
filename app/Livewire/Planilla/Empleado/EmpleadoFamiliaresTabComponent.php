<?php

namespace App\Livewire\Planilla\Empleado;

use App\Models\EmpleadoDerechoHabiente;
use Livewire\Component;

/**
 * Pestaña "Derecho habientes" del panel del empleado. Lista los vínculos y delega la gestión
 * al wizard existente (DerechoHabienteWizardComponent), que también usa su propia página.
 */
class EmpleadoFamiliaresTabComponent extends Component
{
    public int $empleadoId;

    protected $listeners = ['derechoHabienteGuardado' => '$refresh'];

    public function render()
    {
        return view('livewire.planilla.empleado.empleado-familiares-tab-component', [
            'vinculos' => EmpleadoDerechoHabiente::with('derechoHabiente')
                ->where('empleado_id', $this->empleadoId)
                ->orderByDesc('activo')
                ->orderBy('anio_vigencia')
                ->orderBy('mes_vigencia')
                ->get(),
        ]);
    }
}
