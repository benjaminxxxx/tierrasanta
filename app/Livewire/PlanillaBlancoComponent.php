<?php

namespace App\Livewire;

use App\Models\PlanMensual;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

class PlanillaBlancoComponent extends Component
{
    use ConSelectorMes;
    public $informacionPlanilla;
    public $search = '';
    public $componente = 'blanco';
    public $sePuedeVerNegro = false;
    protected $listeners = ['actualizado' => '$refresh'];

    public function mount()
    {
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
    }
    public function ver($componente)
    {
        $this->componente = $componente;
    }
    public function render()
    {
        if ($this->mes && $this->anio) {
            $informacionBlanco = PlanMensual::where('mes', $this->mes)->where('anio', $this->anio)->first();
            if ($informacionBlanco) {
                $this->sePuedeVerNegro = $informacionBlanco->detalle->count() > 0;
            }
        }
        return view('livewire.planilla-blanco-component');
    }
}
