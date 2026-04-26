<?php

namespace App\Livewire\GestionReportes;

use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

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
        return view('livewire.gestion-reportes.reporte-mensual-component');
    }
}
