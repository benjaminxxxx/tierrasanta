<?php

namespace App\Livewire;

use App\Models\Empleado;
use Livewire\Component;

class ReporteDiarioComponent extends Component
{
    public $empleados;
    public function mount(){
        $this->empleados = Empleado::where('status','activo')->orderBy('orden')->get();
       
    }
    public function render()
    {

        return view('livewire.reporte-diario-component');
    }
}
