<?php

namespace App\Livewire;

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
        return view('livewire.nutrientes-component');
    }
}
