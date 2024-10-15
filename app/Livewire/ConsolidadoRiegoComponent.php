<?php

namespace App\Livewire;

use App\Models\ConsolidadoRiego;
use Date;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsolidadoRiegoComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $consolidado_riegos;
    protected $listeners = ['RefrescarMapa'=>'$refresh','registroConsolidado'=>'$refresh'];
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
    public function consolidarRegistro($documento){
        if(!$documento || !$this->fecha){
            return;
        }
        try {
            $this->dispatch('consolidarRegador',$documento,$this->fecha);
            $this->alert("success","Registro consolidado");
       
        } catch (\Throwable $th) {
            $this->alert("error","Ocurrió un error al consolidar el registro");
        }
    }
}
