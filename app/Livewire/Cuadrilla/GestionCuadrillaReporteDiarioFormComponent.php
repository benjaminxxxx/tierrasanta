<?php

namespace App\Livewire\Cuadrilla;
use App\Models\CuadRegistroDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Campo\Labor\LaboresServicio;
use App\Services\Planilla\Empleado\ActividadServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaReporteDiarioFormComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts, \App\Livewire\Traits\ConActividadesCampo;
    public $mostrarFormularioRegistroDiarioCuadrilla = false;
    public $cuadrilleros = [];
    public $labores = [];
    public $cuadrillerosAgregados = [];
    public $actividades = [];
    public $todosCuadrilleros = [];
    public $cuadrilleroSeleccionado;
    public $fecha;
    protected $listeners = ['registrarReporteDiarioCuadrilla', 'guardarRegistroActividadesConfirmado'];
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
    /** "Sí, es correcto" de las actividades por campo */
    public function guardarRegistroActividadesConfirmado($data = null)
    {
        try {
            $this->aceptarActividadesCampo($this->fecha, $data);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
            return;
        }
        $this->guardarRegistroDiarioCuadrilla(true);
    }

    public function guardarRegistroDiarioCuadrilla(bool $actividadesConfirmadas = false)
    {
        try {
            // Un campo con más de un código ese día: "¿es correcto?" antes de guardar (aquí se agregan horas, no se reemplazan)
            $tramos = [];
            foreach ($this->actividades as $a) {
                foreach ($this->cuadrillerosAgregados as $c) {
                    $tramos[] = ['campo' => $a['campo'] ?? '', 'labor' => $a['labor'] ?? '', 'origen' => 'cuadrilla', 'trabajador_id' => $c['id'] ?? null];
                }
            }
            if (!$actividadesConfirmadas && $this->fecha && $this->preguntarActividadesCampo($this->fecha, $tramos, [], [], 'guardarRegistroActividadesConfirmado', [])) {
                return;
            }
            CuadrilleroServicio::registrarActividadDiaria(
                $this->fecha,
                $this->cuadrillerosAgregados,
                $this->actividades
            );
            ActividadServicio::detectarYCrearActividades($this->fecha);

            $this->mostrarFormularioRegistroDiarioCuadrilla = false;

            $this->alert('success', 'Actividades registradas correctamente.');
            $this->dispatch('registroDetalleHorasExterno', $this->fecha);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        return view('livewire.cuadrilla.gestion-cuadrilla-reporte-diario-form-component');
    }
}