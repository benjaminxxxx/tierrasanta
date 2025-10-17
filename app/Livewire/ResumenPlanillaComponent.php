<?php

namespace App\Livewire;

use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;
use Carbon\Carbon;
use Session;

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
        return view('livewire.resumen-planilla-component');
    }
}
