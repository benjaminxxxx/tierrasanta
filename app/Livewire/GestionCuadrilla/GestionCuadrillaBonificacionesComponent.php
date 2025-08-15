<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\Actividad;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Session;

class GestionCuadrillaBonificacionesComponent extends Component
{
    public $fecha;
    public $actividades = [];
    public $actividadSeleccionada;
    public function mount()
    {
        $this->fecha = Session::get('fecha_reporte', Carbon::now()->format('Y-m-d'));
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
    
    public function fechaAnterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
        $this->obtenerActividades();
    }

    public function fechaPosterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
        $this->obtenerActividades();
    }
    public function updatedFecha($fecha)
    {
        Session::put('fecha_reporte', $fecha);
        $this->obtenerActividades();
    }
    
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-bonificaciones-component');
    }
}