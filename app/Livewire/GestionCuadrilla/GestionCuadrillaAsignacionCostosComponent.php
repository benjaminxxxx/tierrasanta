<?php

namespace App\Livewire\GestionCuadrilla;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class GestionCuadrillaAsignacionCostosComponent extends Component
{
    use LivewireAlert;
    public $fechaInicio;
    public $fechaFin;
    public $costosAsignados = [];
    public $totalDias = 0;
    public $headers = [];
    public $mostrarFormularioAignacionCostos = false;
    protected $listeners = ['asignarCostosPorFecha'];
    public function mount()
    {
        $this->obtenerCostosAsignados();
    }
    public function asignarCostosPorFecha($fechaInicio, $fechaFin)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->mostrarFormularioAignacionCostos = true;
        $this->obtenerCostosAsignados(true);
    }
    public function obtenerCostosAsignados($usarDispatch = false)
    {
        $resultado = CuadrilleroServicio::obtenerHandsontableCostosAsignados($this->fechaInicio, $this->fechaFin);
        $this->costosAsignados = $resultado['data'];
        $this->totalDias = $resultado['total_dias'];
        $this->headers = $resultado['headers'];
        if ($usarDispatch) {
            $this->dispatch('actualizarTablaAsignacionCosto', $this->costosAsignados, $this->totalDias, $this->headers);
        }
    }
    public function guardarAsignacionCostos($datos)
    {
        if (!$this->fechaInicio) {
            $hoy = Carbon::today();
            $this->fechaInicio = $hoy->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        CuadrilleroServicio::guardarCostosDiariosGrupo($datos, $this->fechaInicio);
        CuadrilleroServicio::guardarPrecioSugerido($datos);

        $this->alert('success', 'Costos asignados guardados correctamente.');
        $this->mostrarFormularioAignacionCostos = false;
        $this->obtenerCostosAsignados(); // refresca tabla
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-asignacion-costos-component');
    }
}