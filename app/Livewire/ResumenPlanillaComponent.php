<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use Session;

class ResumenPlanillaComponent extends Component
{
    public $anio;
    public $mes;
    public function mount($mes = null, $anio = null)
    {
        $this->mes = Session::get('fecha_reporte_mes', Carbon::now()->format('m'));
        $this->anio = Session::get('fecha_reporte_anio',Carbon::now()->format('Y'));
    }
    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
    }

    public function mesSiguiente()
    {
       $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
    }
    public function updatedMes($valor)
    {
        
        $fecha = Carbon::createFromDate($this->anio, $valor, 1);

        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
    }
    public function updatedAnio($anio)
    {
        $fecha = Carbon::createFromDate($anio, $this->mes, 1);
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
    }
    public function render()
    {
        return view('livewire.resumen-planilla-component');
    }
}
