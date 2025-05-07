<?php

namespace App\Livewire;

use App\Models\PoblacionPlantas;
use App\Services\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCampoPoblacionPlantasComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $campoFiltrado;
    public $detalleComponentId;
    public $campaniaUnica;
    public $campaniaId;
  
    protected $listeners = ['poblacionPlantasRegistrado' => '$refresh', 'confirmarEliminarPoblacionPlanta'];
   
    public function mount($campaniaId = null,$campaniaUnica=false){
        $this->campaniaId = $campaniaId;
        $this->campaniaUnica = $campaniaUnica;
    }
    public function verDetallePoblacion($poblacionPlantaId)
    {
        $this->detalleComponentId = $poblacionPlantaId;
    }
 
    public function updatedCampoFiltrado()
    {
        $this->resetPage();
    }
    public function eliminarPoblacionPlanta($poblacionId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminarPoblacionPlanta',
            'data' => [
                'poblacionId' => $poblacionId,
            ],
        ]);
    }
    public function enviarHistorialPoblacionPlantas($campaniaId)
    {
        try {
            $campaniaServicio = new CampaniaServicio($campaniaId);
            $campaniaServicio->registrarHistorialPoblacionPlantas();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function confirmarEliminarPoblacionPlanta($data)
    {
        try {
            $poblacionId = $data['poblacionId'];

            $poblacion = PoblacionPlantas::findOrFail($poblacionId);
            $campaniaId = $poblacion->campania->id;
            $poblacion->delete();
            $this->alert('success', 'Registro Eliminado Correctamente.');
            $this->enviarHistorialPoblacionPlantas($campaniaId);
            $this->dispatch('poblacionPlantasEliminado');

        } catch (\Throwable $th) {
            return $this->alert('success', 'El registro ya no existe.');
        }
    }
    public function render()
    {

        $query = PoblacionPlantas::when($this->campoFiltrado,function($query)  {

            $campo = $this->campoFiltrado;
            return $query->whereHas('campania',function ($q) use($campo) {
                return $q->where('campo',$campo);
            });
        })->with(['campania']);

       
        if($this->campaniaUnica){
            $query->where('campania_id',$this->campaniaId);
        }

        $poblacionPlantas = $query->paginate(20);
        return view('livewire.reporte-campo-poblacion-plantas-component', [
            'poblacionPlantas' => $poblacionPlantas
        ]);
    }
}
