<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampaniaPorCampoInformeComponent extends Component
{
    use LivewireAlert;
    public $campania;
    protected $listeners = ['poblacionPlantasRegistrado'=>'refrescar'];
    public function mount($campania){
        $this->campania = CampoCampania::find($campania);
    }
    public function refrescar(){
        $this->campania->refresh();
    }
    public function render()
    {
        return view('livewire.gestion-campania.campania-por-campo-informe-component');
    }
}
