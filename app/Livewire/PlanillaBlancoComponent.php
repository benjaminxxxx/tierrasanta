<?php

namespace App\Livewire;

use App\Models\PlanillaBlanco;
use App\Models\PlanillaBlancoDetalle;
use Carbon\Carbon;
use Livewire\Component;

class PlanillaBlancoComponent extends Component
{
    public $informacionPlanilla;
    public $mes;
    public $anio;
    public $search = '';
    public $componente = 'blanco';
    public $sePuedeVerNegro = false;
    protected $listeners = ['actualizado'=>'$refresh'];
    
    public function mount()
    {
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
    
    public function ver($componente){
        $this->componente = $componente;
    }
    public function render()
    {
        if($this->mes && $this->anio){
            $informacionBlanco = PlanillaBlanco::where('mes', $this->mes)->where('anio', $this->anio)->first();
            if($informacionBlanco){
                $this->sePuedeVerNegro = $informacionBlanco->detalle->count()>0;
            }
        }
        return view('livewire.planilla-blanco-component');
    }
}
