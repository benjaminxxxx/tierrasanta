<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;

class AlmacenSalidaCombustibleComponent extends Component
{
    public $anio;
    public $mes;
    public $tipo;
    public function mount($mes = null, $anio = null)
    {
        $this->tipo="combustible";
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
        return view('livewire.almacen-salida-combustible-component');
    }
}
