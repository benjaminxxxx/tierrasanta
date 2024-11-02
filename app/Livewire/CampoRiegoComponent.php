<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use App\Models\ReporteDiarioRiego;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoRiegoComponent extends Component
{
    use LivewireAlert;
    public $campos;
    public $regadores;
    public $fecha;
    public $regadorSeleccionado;
    public $estaConsolidado;
    public function mount()
    {
        $this->fecha = Carbon::now()->format('Y-m-d');
        if($this->fecha){
            $this->regadores = ReporteDiarioRiego::whereDate('fecha',$this->fecha)->get()->pluck('regador','documento')->toArray();
        }
    }
    public function render()
    {
        $this->campos = Campo::all();
        return view('livewire.campo-riego-component');
    }

}
