<?php

namespace App\Livewire;

use App\Models\PoblacionPlantas;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCampoPoblacionPlantasComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $campoFiltrado;
    public $detalleComponentId;
  
    protected $listeners = ['poblacionPlantasRegistrado' => '$refresh', 'confirmarEliminarPoblacionPlanta'];
   
 
    public function verDetallePoblacion($poblacionPlantaId)
    {
        $this->detalleComponentId = $poblacionPlantaId;
    }
    public function preguntarEliminarPoblacionPlanta($poblacionPlantaId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminarPoblacionPlanta',
            'data' => [
                'poblacionPlantaId' => $poblacionPlantaId,
            ],
        ]);
    }
    public function confirmarEliminarPoblacionPlanta($data)
    {
        try {
            $poblacionPlantaId = $data['poblacionPlantaId'];
            $poblacionPlanta = PoblacionPlantas::findOrFail($poblacionPlantaId);
            $poblacionPlanta->delete();
            $this->alert('success', 'El registro ha sido eliminado correctamente.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('log', 'Registro no encontrado: ' . $e->getMessage());
            $this->alert('error', 'El registro no existe o ya fue eliminado.');
        } catch (\Exception $e) {
            $this->dispatch('log', 'Error al eliminar: ' . $e->getMessage());
            $this->alert('error', 'Ocurrió un error interno al intentar eliminar el registro.');
        }
    }
    public function updatedCampoFiltrado()
    {
        $this->resetPage();
    }
    public function render()
    {
        $query = PoblacionPlantas::query();
        if ($this->campoFiltrado) {
            $query->where('lote', $this->campoFiltrado);
        }
        $poblaciones = $query->paginate(20);
        return view('livewire.reporte-campo-poblacion-plantas-component', [
            'poblaciones' => $poblaciones
        ]);
    }
}
