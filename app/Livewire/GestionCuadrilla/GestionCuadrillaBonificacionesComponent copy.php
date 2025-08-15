<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\Actividad;
use App\Services\Campo\ActividadesServicio;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class GestionCuadrillaBonificacionesComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $actividades = [];
    public $actividadSeleccionada;
    public $registros = [];
    public $total_horarios = 0;
    public $tramos = [
        ['hasta' => '', 'monto' => '']
    ];
    public $estandarProduccion = [];
    public $unidades = 'kg.';
    public $recojos = 1;
    public function mount()
    {
        $this->fecha = Session::get('fecha_reporte', Carbon::now()->format('Y-m-d'));
        $this->obtenerActividades();
    }
    public function updatedActividadSeleccionada()
    {
        $this->cargarDatosActividad();
    }
    public function cargarDatosActividad()
    {
        if (!$this->actividadSeleccionada) {
            $this->dispatch('actualizarTablaBonificacionesCuadrilla', [], [], 0);
            $this->reset(['actividadSeleccionada']);
            $this->recojos = 1;
            return;
        }
        try {
            $registros = CuadrilleroServicio::obtenerHandsontableRegistrosPorActividad($this->actividadSeleccionada);
            $registrosPlanilla = PlanillaServicio::obtenerHandsontableRegistrosPorActividad($this->actividadSeleccionada);

            // Asegurar que los datos vengan como arrays aunque estén vacíos
            $dataCuadrilla = collect($registros['data'] ?? [])->map(function ($item) {
                $item['tipo'] = 'CUADRILLA';
                return $item;
            })->all();

            $dataPlanilla = collect($registrosPlanilla['data'] ?? [])->map(function ($item) {
                $item['tipo'] = 'PLANILLA';
                return $item;
            })->all();

            // Unir los datos
            $mergedData = array_merge($dataCuadrilla, $dataPlanilla);

            // Calcular el total de horarios como el máximo entre ambos
            $totalHorarios = max(
                $registros['total_horarios'] ?? 0,
                $registrosPlanilla['total_horarios'] ?? 0
            );

            // Asignar al componente Livewire
            $this->registros = $mergedData;
            $this->total_horarios = $totalHorarios;
            $labor = ActividadesServicio::obtenerEstandarProduccion($this->actividadSeleccionada);
            $this->tramos = $labor['tramos_bonificacion'];
            $this->estandarProduccion = $labor['estandar_produccion'];
            $this->unidades = $labor['unidades'];
            $this->recojos = 

            $this->dispatch('actualizarTablaBonificacionesCuadrilla', $this->registros, $this->total_horarios);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function guardarBonificaciones($datos)
    {
        try {
            $data = [
                'tramos_bonificacion' => json_encode($this->tramos),
                'unidades' => $this->unidades,
                'estandar_produccion' => $this->estandarProduccion
            ];
            
            ActividadServicio::actualizarConfiguracionActividad($data,$this->actividadSeleccionada);
            EmpleadoServicio::guardarBonificaciones($this->fecha,$datos);
            
            CuadrilleroServicio::calcularCostosCuadrilla($this->fecha);
            $this->alert('success', 'Bonificaciones actualizadas correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
  
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-bonificaciones-component');
    }
}