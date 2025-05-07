<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\PoblacionPlantas;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PoblacionPlantasPorCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campania;
    public $poblacionPlantas = [];
    protected $listeners = ['poblacionPlantasRegistrado' => 'refrescarCampania','poblacionPlantasEliminado' => 'refrescarCampania'];
    public function mount($campaniaId)
    {
        $this->campania = CampoCampania::find($campaniaId);
    }
    public function refrescarCampania()
    {
        $this->campania->refresh();
    }
   
    public function render()
    {
        return view('livewire.poblacion-plantas-por-campania-component');
    }
}
