<?php

namespace App\Livewire\GestionNutriente;

use App\Models\Nutriente;
use Livewire\Component;

class NutrientesComponent extends Component
{
    public $nutrientes = [];
    public function mount(){
        $this->nutrientes = Nutriente::all();
    }
    public function render()
    {
        return view('livewire.gestion-nutriente.nutrientes-component');
    }
}
