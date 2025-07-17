<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\Actividad;
use App\Services\Campo\ActividadesServicio;
use App\Services\Cuadrilla\CuadrilleroServicio;
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
    public function mount()
    {
        $this->fecha = Session::get('fecha_reporte', Carbon::now()->format('Y-m-d'));
        $this->obtenerActividades();
    }
    public function obtenerActividades()
    {
        $this->dispatch('actualizarTablaBonificacionesCuadrilla', [], [], 0);
        $this->reset(['actividadSeleccionada', 'estandarProduccion', 'tramos', 'unidades']);
        if (!$this->fecha) {
            return;
        }
        $this->actividades = Actividad::where('fecha', $this->fecha)->get();
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
            return;
        }
        try {
            $registros = CuadrilleroServicio::obtenerHandsontableRegistrosPorActividad($this->actividadSeleccionada);
            $this->registros = $registros['data'];
            $this->total_horarios = $registros['total_horarios'];
            $labor = ActividadesServicio::obtenerEstandarProduccion($this->actividadSeleccionada);
            $this->tramos = $labor['tramos_bonificacion'];
            $this->estandarProduccion = $labor['estandar_produccion'];
            $this->unidades = $labor['unidades'];

            $this->dispatch('actualizarTablaBonificacionesCuadrilla', $this->registros, $this->total_horarios);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    #region Fechas
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
    public function guardarBonificaciones($datos)
    {
        try {
            CuadrilleroServicio::guardarBonificacionesYConfiguracionActividad(
                $this->actividadSeleccionada,
                $datos,
                $this->tramos,
                $this->unidades,
                $this->estandarProduccion
            );
            CuadrilleroServicio::calcularCostosCuadrilla($this->fecha);
            $this->alert('success', 'Bonificaciones actualizadas correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    #endregion
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-bonificaciones-component');
    }
}