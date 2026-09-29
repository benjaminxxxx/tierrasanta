<?php

namespace App\Livewire\Campo;

use App\Models\Maquinaria;
use Livewire\Component;

class MaquinariasComponent extends Component
{

    public $maquinarias = [];
    protected $listeners = ['ActualizarMaquinarias' => '$refresh'];
    public function render()
    {
        $this->maquinarias = Maquinaria::with('combustible.tabla6')->orderBy('nombre')->get();
        return view('livewire.campo.maquinarias-component');
    }
}
