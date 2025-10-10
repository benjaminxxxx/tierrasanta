<?php

namespace App\Livewire;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;
use  App\Models\PlanEmpleado;
use  App\Models\AsignacionFamiliar;
class AsignacionFamiliarListComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $search = '';
    protected $listeners = ['confirmarEliminarHijo'];
    public $asignacionId;
    public function render()
    {
        $query = AsignacionFamiliar::query()
            ->with('empleado'); // Incluye la relación con Empleado

        if (!empty($this->search)) {
            $query->where(function ($query) {
                $query->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('documento', 'like', '%' . $this->search . '%')
                    ->orWhereHas('empleado', function ($query) {
                        $query->where('nombres', 'like', '%' . $this->search . '%')
                            ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                            ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                            ->orWhere('documento', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $asignacion = $query->paginate(20);

        return view('livewire.asignacion-familiar-list-component', [
            'asignaciones' => $asignacion
        ]);
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function confirmarEliminacion($id)
    {
        $this->asignacionId = $id;

        $this->alert('question', '¿Está seguro(a) que desea eliminar el Hijo del Empleado?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminarHijo',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function confirmarEliminarHijo()
    {
        if ($this->asignacionId) {
            $asignacion = AsignacionFamiliar::find($this->asignacionId);
            if ($asignacion) {
                $asignacion->delete();
                $this->alert('success','Se ha eliminado el registro');
            }else{
                $this->alert('error','No se ha eliminado el registro');
            }
        }
    }
    public function actualizarEstado($id, $estaEstudiando)
    {
        $asignacion = AsignacionFamiliar::find($id);
        
        if ($asignacion) {
            $asignacion->update([
                'esta_estudiando' => $estaEstudiando ? 1 : 0,
            ]);
        }
    }
}
