<?php

namespace App\Livewire\GestionPlanilla;

use App\Services\PlanSueldoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ValidarSueldoPlanillaComponent extends Component
{
    use LivewireAlert;

    // Estado de la Vista
    public bool $mostrarValidarSueldosEmpleados = false;
    public int $mes;
    public int $anio;

    // Formularios e Insumos
    public array $sueldos = [];
    public array $fechas = [];
    public $empleadosSinSueldo = [];

    protected $listeners = ['mostrarValidadorSueldos'];

    public function mount(int $mes, int $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->revisarSueldosMensuales();
    }

    public function mostrarValidadorSueldos()
    {
        $this->mostrarValidarSueldosEmpleados = true;
    }

    /**
     * Paso 1: Evaluar qué empleados no tienen un sueldo vigente en el periodo.
     */
    public function revisarSueldosMensuales()
    {
        try {
            // Resolvemos el servicio directamente desde el contenedor de Laravel
            $sueldoServicio = app(PlanSueldoServicio::class);

            $this->empleadosSinSueldo = $sueldoServicio->obtenerEmpleadosSinSueldoEnPeriodo($this->mes, $this->anio);

            $hayPendientes = $this->empleadosSinSueldo->isNotEmpty();
            $this->dispatch('resultadoSueldosValidados', hayEmpleadosSinSueldo: $hayPendientes);

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    /**
     * Paso 2: Registrar en lote los sueldos ingresados por el usuario.
     */
    public function registrarSueldos(PlanSueldoServicio $sueldoServicio)
    {
        try {
            // 2.1 Validar consistencia de pares (Sueldo + Fecha)
            $errores = $this->validarInsumosFormulario();
            if (!empty($errores)) {
                $this->alert('warning', implode('<br>', $errores));
                return;
            }

            // 2.2 Guardar a través del servicio
            $totalGuardados = $sueldoServicio->crearMasivo($this->sueldos, $this->fechas);

            if ($totalGuardados === 0) {
                $this->alert('info', 'No ingresaste datos para procesar.');
                return;
            }

            // 2.3 Éxito, limpiar y re-evaluar
            $this->alert('success', "Se registraron {$totalGuardados} sueldos correctamente.");
            $this->reset(['sueldos', 'fechas']);
            $this->revisarSueldosMensuales();

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    /**
     * Helper privado: Valida que si ingresan un sueldo, no falte la fecha (y viceversa).
     */
    private function validarInsumosFormulario(): array
    {
        $errores = [];

        foreach ($this->empleadosSinSueldo as $empleado) {
            $id = $empleado->id;
            $tieneSueldo = !empty(trim($this->sueldos[$id] ?? ''));
            $tieneFecha = !empty(trim($this->fechas[$id] ?? ''));

            if ($tieneSueldo && !$tieneFecha) {
                $errores[] = "El empleado {$empleado->nombres} tiene sueldo pero le falta la fecha de inicio.";
            }

            if (!$tieneSueldo && $tieneFecha) {
                $errores[] = "El empleado {$empleado->nombres} tiene fecha pero le falta el monto de sueldo.";
            }
        }

        return $errores;
    }

    public function render()
    {
        return view('livewire.gestion-planilla.validar-sueldo-planilla-component');
    }
}