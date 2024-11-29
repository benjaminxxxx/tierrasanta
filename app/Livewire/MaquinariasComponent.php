<?php

namespace App\Livewire;

use App\Models\Maquinaria;
use Livewire\Component;

class MaquinariasComponent extends Component
{

    public $maquinarias = [];
    protected $listeners = ['ActualizarMaquinarias' => '$refresh'];
    public function render()
    {
        $this->maquinarias = Maquinaria::all();
        return view('livewire.maquinarias-component');
    }
}
