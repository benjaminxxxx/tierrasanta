<?php

namespace App\Livewire;

use App\Models\PlanEmpleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class ReporteDiarioComponent extends Component
{
    public $empleados;
    public $fecha;
    public function mount(){
        //$this->fecha = Carbon::now()->format("Y-m-d");
        
        $this->fecha = Session::get('fecha_reporte', Carbon::now()->format('Y-m-d'));
        $this->empleados = PlanEmpleado::where('status','activo')->orderBy('orden')->get();
       
    }
    public function fechaAnterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
        
    }

    public function fechaPosterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
    }
    public function updatedFecha()
    {
        Session::put('fecha_reporte', $this->fecha);
    }
    public function render()
    {
        return view('livewire.reporte-diario-component');
    }
}
