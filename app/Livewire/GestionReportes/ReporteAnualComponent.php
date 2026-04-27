<?php

namespace App\Livewire\GestionReportes;

use App\Traits\Selectores\ConSelectorAnio;
use Livewire\Component;

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
        return view('livewire.gestion-reportes.reporte-anual-component');
    }
}
