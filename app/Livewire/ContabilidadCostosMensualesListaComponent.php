<?php

namespace App\Livewire;

use App\Models\CostoMensual;
use Livewire\Component;
use Livewire\WithPagination;

class ContabilidadCostosMensualesListaComponent extends Component
{
    use WithPagination;
    public $verCostoNegro = false;
    public function mount()
    {

    }
    public function render()
    {
        $costos = CostoMensual::paginate( 20);
        return view('livewire.contabilidad-costos-mensuales-lista-component', [
            'costos' => $costos
        ]);
    }
}
