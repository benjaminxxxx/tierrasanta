<?php

namespace App\Livewire\Reporte;

use Livewire\Attributes\Title;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

#[Title('Reporte Mensual')]
class ReporteMensualComponent extends Component
{
    use ConSelectorMes;

    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado($anio, $mes){

    }
    public function render()
    {
        return view('livewire.reporte.reporte-mensual-component');
    }
}
