<?php

namespace App\Livewire;

use App\Models\CostoMensual;
use Livewire\Component;

class ContabilidadCostosMensualesListaComponent extends Component
{
    public $verCostoNegro = false;
    public function mount()
    {

    }
    public function render()
    {
        $costos = CostoMensual::paginate(20);
        return view('livewire.contabilidad-costos-mensuales-lista-component', [
            'costos' => $costos
        ]);
    }
}
