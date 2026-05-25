<?php

namespace App\Livewire;

use App\Models\CampoCampania;
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


    #region RENDER
    public function render()
    {
        return view('livewire.evaluacion-brotes-x-piso-por-campania-component');
    }
    #endregion
}
