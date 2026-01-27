<?php

namespace App\Livewire\GestionCuadrilla;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
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
    public $tramoLaboral;
    public $tramoId;
    protected $listeners = ['asignarCostosPorFecha'];
    public function mount()
    {
        
    }
    public function reInicializar($tramoId){
        $this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
        if($this->tramoLaboral){
           $this->fechaInicio = $this->tramoLaboral->fecha_inicio;
           $this->fechaFin = $this->tramoLaboral->fecha_fin; 
        }
        $this->obtenerCostosAsignados();
    }
    public function asignarCostosPorFecha($tramoId)
    {
        $this->mostrarFormularioAignacionCostos = true;
        $this->reInicializar($tramoId);
    }
    public function obtenerCostosAsignados()
    {
        $resultado = CuadrilleroServicio::obtenerHandsontableCostosAsignados($this->tramoLaboral->id);
        $this->costosAsignados = $resultado['data'];
        $this->totalDias = $resultado['total_dias'];
        $this->headers = $resultado['headers'];
        $this->dispatch('actualizarTablaAsignacionCosto', $this->costosAsignados, $this->totalDias, $this->headers);
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
        $this->dispatch('costosSemanalesModificados');
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-asignacion-costos-component');
    }
}