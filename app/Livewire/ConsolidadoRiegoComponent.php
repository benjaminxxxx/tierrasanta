<?php

namespace App\Livewire;

use App\Models\ConsolidadoRiego;
use Date;
use Livewire\Component;

class ConsolidadoRiegoComponent extends Component
{
    public $fecha;
    public $consolidado_riegos;
    protected $listeners = ['RefrescarMapa'=>'$refresh'];
    public function mount()
    {
        $this->fecha = (new \DateTime('now'))->format('Y-m-d');
    }
    public function render()
    {
        if ($this->fecha) {
            $this->consolidado_riegos = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();
        }
        return view('livewire.consolidado-riego-component');
    }
    public function fechaAnterior()
    {
        // Restar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
    }

    public function fechaPosterior()
    {
        // Sumar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
    }
    public function consolidarRegistro(){
        $this->dispatch('ConsolidarRegadores',$this->fecha);
    }
}
