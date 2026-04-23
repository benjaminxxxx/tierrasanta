<?php

namespace App\Livewire\GestionReportes;

use App\Livewire\Traits\ConFechaReporteDia;
use Livewire\Component;

class ReporteDiarioComponent extends Component
{
    use ConFechaReporteDia;

    public function mount()
    {
        $this->inicializarFecha();
    }
    protected function despuesFechaModificada($fecha){

    }
    public function render()
    {
        return view('livewire.gestion-reportes.reporte-diario-component');
    }
}
