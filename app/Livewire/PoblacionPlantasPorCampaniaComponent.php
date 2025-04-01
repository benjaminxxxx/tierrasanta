<?php

namespace App\Livewire;

use App\Models\PoblacionPlantas;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PoblacionPlantasPorCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campaniaId;
    public $poblacionPlantas = [];
    protected $listeners = ['poblacionPlantasRegistrado' => 'obtenerPoblacionPlantas','confirmarEliminarPoblacionPlanta'];
    public function mount($campaniaId)
    {
        $this->campaniaId = $campaniaId;
        $this->obtenerPoblacionPlantas();
    }
    public function obtenerPoblacionPlantas()
    {
        if (!$this->campaniaId) {
            return;
        }
        $this->poblacionPlantas = PoblacionPlantas::where('campania_id', $this->campaniaId)->get();
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
    public function confirmarEliminarPoblacionPlanta($data)
    {
        try {
            $poblacionId = $data['poblacionId'];

            $poblacion = PoblacionPlantas::findOrFail($poblacionId);
            $poblacion->delete();
            $this->alert('success', 'Registro Eliminado Correctamente.');
            $this->obtenerPoblacionPlantas();

        } catch (\Throwable $th) {
            return $this->alert('success', 'El registro ya no existe.');
        }
    }
    public function render()
    {
        return view('livewire.poblacion-plantas-por-campania-component');
    }
}
