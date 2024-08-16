<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Empleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;

class EmpleadosComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $isFormOpen = true;
    public $empleadoCode;
    public $search = '';
    protected $listeners = ['EmpleadoRegistrado' => '$refresh', 'eliminacionConfirmada'];
    public function render()
    {
        $query = Empleado::query();

        if (!empty($this->search)) {
            $query->where(function ($query) {
                $query->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                    ->orWhere('documento', 'like', '%' . $this->search . '%');
            });
        }

        $empleados = $query->paginate(20);

        return view('livewire.empleados-component', [
            'empleados' => $empleados
        ]);
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function editar($code)
    {
        $this->dispatch('EditarEmpleado', $code);
    }
    public function eliminacionConfirmada()
    {
        if ($this->empleadoCode) {
            $empleado = Empleado::where('code', $this->empleadoCode);
            if ($empleado) {
                $empleado->delete();
                $this->empleadoCode = null;
            }
        }
    }
    public function enable($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'activo';
            $empleado->save();
        }
    }

    public function disable($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'inactivo';
            $empleado->save();
        }
    }
    public function confirmarEliminacion($code)
    {
        $this->empleadoCode = $code;

        $this->alert('question', '¿Está seguro que desea eliminar al Empleado?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionConfirmada',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
}
