<?php

namespace App\Livewire;

use App\Models\Empleado;
use Carbon\Carbon;
use Livewire\Component;

class ReporteDiarioComponent extends Component
{
    public $empleados;
    public $fecha;
    public function mount(){
        $this->fecha = Carbon::now()->format("Y-m-d");
        $this->empleados = Empleado::where('status','activo')->orderBy('orden')->get();
       
    }
    public function fechaAnterior()
    {
        // Restar un día a la fecha seleccionada
        $this->fecha = Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
    }

    public function fechaPosterior()
    {
        // Sumar un día a la fecha seleccionada
        $this->fecha = Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
    }
    public function render()
    {
        return view('livewire.reporte-diario-component');
    }
}
