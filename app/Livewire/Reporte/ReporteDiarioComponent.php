<?php

namespace App\Livewire\Reporte;

use Livewire\Attributes\Title;
use App\Livewire\Traits\ConFechaReporteDia;
use Livewire\Component;

#[Title('Reporte Diario')]
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
        return view('livewire.reporte.reporte-diario-component');
    }
}
