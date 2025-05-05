<?php

namespace App\Livewire;

use App\Models\EvaluacionBrotesXPiso;
use App\Services\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCampoEvaluacionBrotesComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $campoFiltrado;
    public $campaniaId;
    public $campaniaUnica;
    protected $listeners = ['poblacionPlantasRegistrado','confirmareliminarBrotesXPiso'];

    public function mount($campaniaId = null,$campaniaUnica=false){
        $this->campaniaId = $campaniaId;
        $this->campaniaUnica = $campaniaUnica;
    }
    public function poblacionPlantasRegistrado(){
        $this->resetPage();
    }
    public function enviarHistorialBrotes($campaniaId){
        try {
            $campaniaServicio = new CampaniaServicio($campaniaId);
            $campaniaServicio->registrarHistorialBrotes();
            $this->dispatch('poblacionPlantasRegistrado');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function duplicar($evaluacionBrotesXPisoId)
    {
        try {
            // Buscar la evaluación original
            $evaluacionOriginal = EvaluacionBrotesXPiso::with('detalles')->find($evaluacionBrotesXPisoId);

            if (!$evaluacionOriginal) {
                return;
            }

            // Crear la nueva evaluación duplicada con la fecha actual
            $nuevaEvaluacion = $evaluacionOriginal->replicate();
            $nuevaEvaluacion->fecha = now(); // Asignar la fecha actual
            $nuevaEvaluacion->save();

            // Duplicar los detalles
            foreach ($evaluacionOriginal->detalles as $detalle) {
                $nuevoDetalle = $detalle->replicate();
                $nuevoDetalle->brotes_x_piso_id = $nuevaEvaluacion->id; // Asignar la nueva evaluación
                $nuevoDetalle->save();
            }
            
            $this->alert('success', 'Evaluación duplicada con éxito');
            $this->enviarHistorialBrotes($nuevaEvaluacion->campania->id);
        } catch (\Throwable $th) {
            $this->dispatch('log',$th->getMessage());
            $this->alert('error', 'Ocurrió un error al intentar duplicar el registro');
        }
    }

    public function eliminarBrotesXPiso($evaluacionBrotesXPisoId)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmareliminarBrotesXPiso',
            'data' => [
                'evaluacionBrotesXPisoId' => $evaluacionBrotesXPisoId,
            ],
        ]);
    }
    public function confirmareliminarBrotesXPiso($data)
    {
        try {
            $evaluacionBrotesXPisoId = $data['evaluacionBrotesXPisoId'];
            $evaluacionBrotesXPiso = EvaluacionBrotesXPiso::findOrFail($evaluacionBrotesXPisoId);
            $campaniaId = $evaluacionBrotesXPiso->campania->id;
            $evaluacionBrotesXPiso->delete();
            $this->alert('success', 'Registro Eliminado Correctamente.');
            $this->enviarHistorialBrotes($campaniaId);

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            return $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        
        $query = EvaluacionBrotesXPiso::when($this->campoFiltrado,function($query)  {

            $campo = $this->campoFiltrado;
            return $query->whereHas('campania',function ($q) use($campo) {
                return $q->where('campo',$campo);
            });
        });

        if($this->campaniaUnica){
            $query->where('campania_id',$this->campaniaId);
        }

        $evaluacionesBrotes = $query->paginate(20);
        return view('livewire.reporte-campo-evaluacion-brotes-component',[
            'evaluacionesBrotes'=>$evaluacionesBrotes
        ]);
    }
}
