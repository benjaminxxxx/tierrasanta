<?php

namespace App\Livewire\Reporte;

use Livewire\Attributes\Title;
use App\Traits\Selectores\ConSelectorAnio;
use Livewire\Component;

#[Title('Reporte Anual')]
class ReporteAnualComponent extends Component
{
    use ConSelectorAnio;

    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesAnioSeleccionado($anio){

    }
    public function render()
    {
        return view('livewire.reporte.reporte-anual-component');
    }
}
