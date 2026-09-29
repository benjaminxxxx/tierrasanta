<?php

namespace App\Livewire\Costos;

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
        return view('livewire.costos.contabilidad-costos-mensuales-lista-component', [
            'costos' => $costos
        ]);
    }
}
