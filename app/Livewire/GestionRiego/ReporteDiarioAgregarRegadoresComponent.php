<?php

namespace App\Livewire\GestionRiego;

use App\Services\Campo\Riego\RiegoServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class ReporteDiarioAgregarRegadoresComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $tipoPersonal;
    public $trabajadores = [];
    public $cuadrilleros = [];
    public $mostrarFormularioAgregarRegador = false;
    public $trabajadoresAgregados = [];
    public $regadorSeleccionado;
    protected $listeners = ['abrirAgregarRegador'];

    public function mount($fecha)
    {
        $this->fecha = $fecha;
        $this->tipoPersonal = 'empleados';
        $this->obtenerTrabajadores();
    }
    public function abrirAgregarRegador(){
        $this->mostrarFormularioAgregarRegador = true;
    }
    public function agregarRegadores()
    {
        try {

            if (is_array($this->trabajadoresAgregados) && count($this->trabajadoresAgregados) == 0) {
                throw new Exception('Debe seleccionar uno o mas regadores');
            }
            RiegoServicio::registrarRegadoresEnFecha($this->fecha, $this->trabajadoresAgregados);
            $this->alert('success', 'Regadores agregados');
            $this->trabajadoresAgregados = [];
            //$this->obtenerRiegos();
            $this->dispatch('nuevosRegadoresHanSidoAgregados');
            $this->mostrarFormularioAgregarRegador = false;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
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
    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-agregar-regadores-component');
    }

}
