<?php

namespace App\Livewire\Evaluacion;

use App\Models\CampoCampania;
use App\Services\Campania\CampaniaServicio;
use App\Support\CalculoHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Exception;

class EvaluacionReinfestacionFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioEvalReinfestacion = false;

    public $campania;

    protected $listeners = ['sincronizarReinformacionInfestacion'];

    public function mount()
    {
    }
    public function sincronizarReinformacionInfestacion($campaniaId)
    {
        try {

            $campaniaServicio = new CampaniaServicio();
            $campaniaServicio->registrarHistorialDeInfestaciones($campaniaId, 'reinfestacion');

            $this->dispatch('refrescarInformeCampaniaXCampo');
            $this->alert('success', 'Datos sincronizados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.evaluacion.evaluacion-reinfestacion-form-component');
    }
}
