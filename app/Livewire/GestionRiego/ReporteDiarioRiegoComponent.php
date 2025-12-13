<?php

namespace App\Livewire\GestionRiego;

use App\Models\ConsolidadoRiego;
use App\Services\Campo\Riego\RiegoServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class ReporteDiarioRiegoComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $consolidados;
    public $archivoBackupHoy;
    public $tipoLabores;
    public $tipoPersonal;
    public $trabajadores = [];
    public $cuadrilleros = [];
    public $mostrarFormularioAgregarRegador = false;
    public $trabajadoresAgregados = [];
    public $regadorSeleccionado;
    protected $listeners = ["generalActualizado", 'obtenerRiegos','registroRiegoEliminado'];
    public function mount()
    {

        $this->fecha = Session::get('fecha_reporte',now()->format('Y-m-d'));
        $this->tipoPersonal = 'empleados';
        $this->obtenerRiegos();
        $this->obtenerTrabajadores();
    }
    public function generalActualizado()
    {
        $this->dispatch('delay-riegos');
    }
    public function registroRiegoEliminado(){
        $this->alert('success','Registro eliminado correctamente');
        $this->obtenerRiegos();
    }
    public function obtenerTrabajadores()
    {
        try {
            $this->trabajadores = EmpleadoServicio::cargarSearchableEmpleados($this->fecha, 'empleados');
            $this->cuadrilleros = EmpleadoServicio::cargarSearchableEmpleados($this->fecha, 'cuadrilleros');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function agregarRegadores()
    {
        try {
            if (is_array($this->trabajadoresAgregados) && count($this->trabajadoresAgregados) == 0) {
                throw new Exception('Debe seleccionar uno o mas regadores');
            }
            RiegoServicio::registrarRegadoresEnFecha($this->fecha,$this->trabajadoresAgregados);
            $this->alert('success', 'Regadores agregados');
            $this->trabajadoresAgregados = [];
            $this->obtenerRiegos();
            $this->mostrarFormularioAgregarRegador = false;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function obtenerRiegos()
    {

        if (!$this->fecha) {
            return;
        }

        $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();

    }
    
    public function updatedFecha($fecha)
    {
        Session::put('fecha_reporte',$fecha);
        $this->obtenerRiegos();
    }
    public function fechaAnterior()
    {
        // Restar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        Session::put('fecha_reporte',$this->fecha);
        $this->obtenerRiegos();
    }

    public function fechaPosterior()
    {
        // Sumar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        Session::put('fecha_reporte',$this->fecha);
        $this->obtenerRiegos();
    }
    public function descargarBackup()
    {
        $this->dispatch('RDRIE_descargarPorFecha', $this->fecha);
    }
    public function descargarBackupCompleto()
    {
        $this->dispatch('RDRIE_descargarBackupCompleto');
    }
    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-riego-component');
    }

}
