<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;

class PlanillaAsistenciaComponent extends Component
{
    public $mes;
    public $anio;
    public $search = '';

    public function mount()
    {
        // Iniciar con el mes y año actuales
        $this->mes = Carbon::now()->format('m');
        $this->anio = Carbon::now()->format('Y');
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
        return view('livewire.planilla-asistencia-component');
    }
}