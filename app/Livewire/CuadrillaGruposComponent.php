<?php

namespace App\Livewire;

use App\Models\GruposCuadrilla;
use Livewire\Component;

class CuadrillaGruposComponent extends Component
{
    public $grupos;
    public function render()
    {
        $this->grupos = GruposCuadrilla::all();
        return view('livewire.cuadrilla-grupos-component');
    }
}
