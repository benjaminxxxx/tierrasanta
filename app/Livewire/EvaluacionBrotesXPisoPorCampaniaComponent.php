<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\EvaluacionBrotesXPiso;
use App\Services\CampaniaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class EvaluacionBrotesXPisoPorCampaniaComponent extends Component
{
    #region TRAITS
    use LivewireAlert;
    #endregion

    #region VARIABLES
    public $campaniaId;
    public $campania;
    public $evaluacionesBrotesXPiso = [];
    protected $listeners = ['confirmareliminarBrotesXPiso', 'poblacionPlantasRegistrado'];
    #endregion
    public $mostrarVacios;
    #region MOUNT
    public function mount($campaniaId)
    {
        $this->mostrarVacios = Session::get('mostrarVacios', false);
        $this->campania = CampoCampania::find($campaniaId);
        if ($this->campania) {
            $this->campaniaId = $campaniaId;
        }
    }
    public function poblacionPlantasRegistrado()
    {
        $this->campania->refresh();
    }
    #endregion

    #region PANEL PRINCIPAL

    /*
    public function enviarHistorialBrotes(){
        try {
            $campaniaServicio = new CampaniaServicio($this->campaniaId);
            $campaniaServicio->registrarHistorialBrotes();
            $this->campania->refresh();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function obtenerEvaluacionesBroteXPiso()
    {
        if (!$this->campaniaId) {
            $this->evaluacionesBrotesXPiso = [];
            return;
        }
        $this->evaluacionesBrotesXPiso = EvaluacionBrotesXPiso::where('campania_id', $this->campaniaId)
            ->orderBy('fecha', 'asc')->get();
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
            $this->enviarHistorialBrotes();
            $this->obtenerEvaluacionesBroteXPiso();
        } catch (\Throwable $th) {
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
            $evaluacionBrotesXPiso->delete();
            $this->alert('success', 'Registro Eliminado Correctamente.');
            $this->enviarHistorialBrotes();
            $this->obtenerEvaluacionesBroteXPiso();

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            return $this->alert('error', $th->getMessage());
        }
    }*/
    #endregion

    #region RENDER
    public function render()
    {
        return view('livewire.evaluacion-brotes-x-piso-por-campania-component');
    }
    #endregion
}
