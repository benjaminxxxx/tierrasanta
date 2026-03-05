<?php

namespace App\Livewire\GestionCuadrilla;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Models\Actividad;
use Livewire\Component;

class GestionCuadrillaBonificacionesComponent extends Component
{ 
    use ConFechaReporteDia;
    public $fecha1;
    public $actividades = [];
    public $actividadSeleccionada;
    public function mount()
    {
        $this->inicializarFecha();
        $this->obtenerActividades();
    }
    protected function despuesFechaModificada(string $fecha)
    {
        $this->obtenerActividades();
    }
    public function obtenerActividades()
    {
        if (!$this->fecha) {
            return;
        }
        $this->reset(['actividadSeleccionada']);
        $this->actividades = Actividad::where('fecha', $this->fecha)->get();
        
    }
    
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-bonificaciones-component');
    }
}