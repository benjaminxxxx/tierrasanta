<?php

namespace App\Livewire\Planilla;

use App\Services\Planilla\PlanillaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PlanillaProyectadaComponent extends Component
{
    use LivewireAlert;
    public $empleados = [];
    public $mes;
    public $anio;
    protected $listeners = ['planillaGnerada' => 'cargarProyeccion'];
    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->cargarProyeccion();
    }
    public function cargarProyeccion(){
        $this->empleados = app(PlanillaServicio::class)->obtenerProyeccion($this->mes,$this->anio);
    }
    public function render()
    {
        return view('livewire.planilla.planilla-proyectada-component');
    }
}