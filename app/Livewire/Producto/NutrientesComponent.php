<?php

namespace App\Livewire\Producto;

use Livewire\Attributes\Title;
use App\Models\Nutriente;
use Livewire\Component;

#[Title('Nutrientes')]
class NutrientesComponent extends Component
{
    public $nutrientes = [];
    public function mount(){
        $this->nutrientes = Nutriente::all();
    }
    public function render()
    {
        return view('livewire.producto.nutrientes-component');
    }
}
