<?php

namespace App\Livewire;

use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

class ContabilidadCostosMensualesComponent extends Component
{
    use ConSelectorMes;
    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        
    }
    public function render()
    {
        return view('livewire.contabilidad-costos-mensuales-component');
    }
}
