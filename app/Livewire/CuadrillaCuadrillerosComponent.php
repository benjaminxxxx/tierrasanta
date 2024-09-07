<?php

namespace App\Livewire;

use App\Models\Cuadrillero;
use Livewire\Component;

class CuadrillaCuadrillerosComponent extends Component
{
    public $cuadrilleros;
    protected $listeners = ['CuadrillerosRegistrados' => '$refresh'];
    public function render()
    {
        $this->cuadrilleros = Cuadrillero::orderBy('codigo_grupo')->orderBy('nombre_completo')->get();
        return view('livewire.cuadrilla-cuadrilleros-component');
    }
}
