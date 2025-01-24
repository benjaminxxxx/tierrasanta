<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;

class ResumenPlanillaComponent extends Component
{
    public $anio;
    public $mes;
    public function mount($mes = null, $anio = null)
    {
        $this->mes = $mes ? $mes : Carbon::now()->format('m');
        $this->anio = $anio ? $anio : Carbon::now()->format('Y');
    }
    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }
    public function render()
    {
        return view('livewire.resumen-planilla-component');
    }
}
