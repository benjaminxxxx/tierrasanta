<?php

namespace App\Livewire\ActividadesDiarias;

use App\Services\Campo\ActividadesServicio;
use Livewire\Component;

class ActividadesDiariasFormComponent extends Component
{
    public $mostrarFormularioActividadDiaria = false;
    public $labores = [];
    public $laboresSeleccion = [];
    public $fecha;
    public $laborSeleccionada;
    protected $listeners = ['crearActividadDiaria'];

    public function mount()
    {
        $this->fecha = now()->format('Y-m-d');
        $this->labores = ActividadesServicio::obtenerLabores();
        $this->laboresSeleccion = $this->labores->map(function ($labor) {
            return [
                'id' => $labor->id,
                'name' => "{$labor->id} - {$labor->nombre_labor}",
            ];
        })->toArray();
    }

    public function crearActividadDiaria()
    {
        $this->mostrarFormularioActividadDiaria = true;
    }


    public function render()
    {
        return view('livewire.actividades-diarias.actividades-diarias-form-component');
    }
}
