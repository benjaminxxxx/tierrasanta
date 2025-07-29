<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\CuadRegistroDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\InformacionGeneral\LaboresServicio;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
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

        $this->obtenerSearchableCuadrilleros();

        $this->labores = LaboresServicio::selectLabores();
    }
    public function obtenerSearchableCuadrilleros()
    {
        if (!$this->fecha) {
            $this->cuadrilleros = [];
            return;
        }
        $this->cuadrilleros = CuadRegistroDiario::whereDate('fecha', $this->fecha)
            ->with(['cuadrillero'])
            ->where('total_horas', '>', 0)
            ->get()
            ->map(fn($registroDiario) => [
                'id' => $registroDiario->cuadrillero_id,
                'name' => $registroDiario->cuadrillero->nombre_completo,
            ])->toArray();
    }
    public function registrarReporteDiarioCuadrilla($fecha = null)
    {

        $this->reset(['cuadrillerosAgregados', 'actividades']);
        $this->fecha = $fecha ? $fecha : Carbon::now()->format("Y-m-d");
        $this->agregarActividad();
        $this->obtenerSearchableCuadrilleros();
        $this->mostrarFormularioRegistroDiarioCuadrilla = true;
    }
    public function updatedFecha(){
        $this->obtenerSearchableCuadrilleros();
        $this->cuadrillerosAgregados = [];
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
            ActividadServicio::detectarYCrearActividades($this->fecha);

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