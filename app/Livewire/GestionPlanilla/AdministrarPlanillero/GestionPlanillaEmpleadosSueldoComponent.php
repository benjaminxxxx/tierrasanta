<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;

use App\Models\PlanEmpleado;
use App\Models\PlanSueldo;
use App\Services\PlanSueldoServicio;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use Exception;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionPlanillaEmpleadosSueldoComponent extends Component
{
    use LivewireAlert;
    public $sueldos = [];
    public $mostrarFormularioEmpleadoSueldo = false;
    public $empleadoId;
    public $fechaInicio;
    public $fechaFin;
    public $sueldo;
    protected $listeners = ['abrirFormularioRegistroEmpleadoSueldo'];

    public function abrirFormularioRegistroEmpleadoSueldo($uuid)
    {
        $empleado = PlanEmpleado::where('uuid', $uuid)->first();
        if (!$empleado) {
            throw new Exception('El empleado ya no existe');
        }
        $this->empleadoId = $empleado->id;
        $this->mostrarFormularioEmpleadoSueldo = true;
        $this->obtenerSueldos();
    }

    public function guardarSueldo()
    {
        $this->validate([
            'fechaInicio' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (date('d', strtotime($value)) != 1) {
                        $fail('La fecha de inicio debe ser siempre el día 1.');
                    }
                }
            ],
            'sueldo' => 'required|numeric|min:0',
        ]);

        try {

            PlanSueldo::create([
                'plan_empleado_id' => $this->empleadoId,
                'fecha_inicio' => $this->fechaInicio,
                'fecha_fin' => $this->fechaFin,
                'sueldo' => $this->sueldo,
                'creado_por' => auth()->id(),
            ]);

            $this->alert('success', 'Sueldo registrado correctamente');
            $this->reset(['fechaInicio', 'fechaFin', 'sueldo']);
            $this->obtenerSueldos();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function eliminarSueldo($sueldoId)
    {
        try {
            
            app(PlanSueldoServicio::class)
                ->eliminar($sueldoId);

            $this->alert('success', 'Sueldo eliminado correctamente');
            $this->obtenerSueldos();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }

    }
    public function obtenerSueldos()
    {
        if (!$this->empleadoId) {
            $this->sueldos = [];
            return;
        }

        $this->sueldos = PlanSueldo::where('plan_empleado_id', $this->empleadoId)
            ->orderBy('fecha_inicio', 'asc')
            ->get();
    }
    private function resetearForm()
    {
        $this->resetErrorBag();
        $this->reset([
        ]);
    }
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados-sueldos');
    }
}
