<?php

namespace App\Livewire;

use App\Models\PlanMensual;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

class PlanillaBlancoComponent extends Component
{
    use ConSelectorMes;
    public $vista = 'Proyectada';
    public $planillaMensual = null;

    public function mount()
    {
        $this->inicializarMesAnio();
        if($this->mes && $this->anio){
            $this->planillaMensual = PlanMensual::where('mes',$this->mes)->where('anio',$this->anio)->first();
        }
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
    }
    public function cambiarVista(string $vista): void
    {
        $this->vista = $vista;
    }
    public function render()
    {

        return view('livewire.planilla-blanco-component');
    }
}
