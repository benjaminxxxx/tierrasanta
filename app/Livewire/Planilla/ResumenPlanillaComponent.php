<?php

namespace App\Livewire\Planilla;

use Livewire\Attributes\Title;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;
use Carbon\Carbon;
use Session;

#[Title('Resumen de Planilla')]
class ResumenPlanillaComponent extends Component
{
    use ConSelectorMes;
    
    public function mount($mes = null, $anio = null)
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio){
       
    }
   
    public function render()
    {
        return view('livewire.planilla.resumen-planilla-component');
    }
}
