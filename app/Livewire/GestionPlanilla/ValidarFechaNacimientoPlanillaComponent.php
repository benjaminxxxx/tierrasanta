<?php

namespace App\Livewire\GestionPlanilla;

use App\Services\PlanEmpleadoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ValidarFechaNacimientoPlanillaComponent extends Component
{
    use LivewireAlert;

    public bool $mostrarValidarFechasNacimiento = false;
    public int $mes;
    public int $anio;
    public int $registro = 1;

    public array $fechasNacimiento = [];
    public $empleadosSinFechaNacimiento = [];

    protected $listeners = ['mostrarValidadorFechasNacimiento'];

    public function mount(int $mes, int $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->revisarFechasNacimientoMensuales();
    }

    public function mostrarValidadorFechasNacimiento()
    {
        $this->mostrarValidarFechasNacimiento = true;
    }

    /**
     * Paso 1: Revisar qué empleados no tienen registrada su fecha de nacimiento.
     */
    public function revisarFechasNacimientoMensuales()
    {
        try {
            $empleadoServicio = app(PlanEmpleadoServicio::class);

            $this->empleadosSinFechaNacimiento = $empleadoServicio->obtenerEmpleadosSinFechaNacimiento($this->mes, $this->anio);

            $hayPendientes = $this->empleadosSinFechaNacimiento->isNotEmpty();
            $this->dispatch('resultadoFechasNacimientoValidadas', hayEmpleadosSinFechaNacimiento: $hayPendientes);

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    /**
     * Paso 2: Registrar en bloque las fechas de nacimiento ingresadas.
     */
    public function registrarFechasNacimiento()
    {
        try {
            if (empty($this->fechasNacimiento)) {
                $this->alert('info', 'No has ingresado ninguna fecha.');
                return;
            }

            $empleadoServicio = app(PlanEmpleadoServicio::class);
            $totalActualizados = $empleadoServicio->actualizarFechasNacimientoMasivo($this->fechasNacimiento);

            if ($totalActualizados > 0) {
                $this->alert('success', "Se actualizaron {$totalActualizados} fechas de nacimiento correctamente.");
                
                $this->reset(['fechasNacimiento']);
                $this->mostrarValidarFechasNacimiento = false;
                
                // Re-evaluamos para quitar la alerta si ya no quedan pendientes
                $this->revisarFechasNacimientoMensuales();
            } else {
                $this->alert('info', 'No se realizaron cambios.');
            }
            $this->registro++;

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-planilla.validar-fecha-nacimiento-planilla-component');
    }
}