<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\Cuadrillero;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\InformacionGeneral\LaboresServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaReporteDiarioFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioRegistroDiarioCuadrilla = false;
    public $cuadrilleros = [];
    public $labores = [];
    public $cuadrillerosAgregados = [];
    public $actividades = [];
    public $todosCuadrilleros = [];
    public $cuadrilleroSeleccionado;
    public $fecha;
    protected $listeners = ['registrarReporteDiarioCuadrilla'];
    public function mount()
    {
        $todos = CuadrilleroServicio::getCuadrillerosCompleto();

        $this->todosCuadrilleros = $todos;

        $this->cuadrilleros = collect($todos)
            ->map(fn($item) => [
                'id' => $item['id'],
                'name' => "{$item['dni']} - {$item['name']}"
            ])
            ->all();

        $this->labores = LaboresServicio::selectLabores();
    }
    public function registrarReporteDiarioCuadrilla()
    {

        $this->reset(['cuadrillerosAgregados', 'actividades']);
        $this->fecha = Carbon::now()->format("Y-m-d");
        $this->agregarActividad();
        $this->mostrarFormularioRegistroDiarioCuadrilla = true;
    }
    public function agregarActividad()
    {
        $this->actividades[] = [
            'inicio' => '',
            'fin' => '',
            'campo' => '',
            'labor' => '',
        ];
    }
    public function removerActividad($index)
    {
        unset($this->actividades[$index]);
        $this->actividades = array_values($this->actividades); // reindexar
    }
    public function guardarRegistroDiarioCuadrilla()
    {
        try {
            CuadrilleroServicio::registrarActividadDiaria(
                $this->fecha,
                $this->cuadrillerosAgregados,
                $this->actividades
            );

            $this->mostrarFormularioRegistroDiarioCuadrilla = false;
            
        $this->alert('success', 'Actividades registradas correctamente.');
            $this->dispatch('cuadrilla_reporte_diario_registrado', $this->fecha);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-diario-form-component');
    }
}