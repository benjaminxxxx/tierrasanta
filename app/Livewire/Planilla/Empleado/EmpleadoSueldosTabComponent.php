<?php

namespace App\Livewire\Planilla\Empleado;

use App\Models\PlanSueldo;
use App\Services\Planilla\PlanSueldoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pestaña "Sueldos" del panel del empleado (reemplaza al modal GestionPlanillaEmpleadosSueldoComponent).
 */
class EmpleadoSueldosTabComponent extends Component
{
    use LivewireAlert;

    public int $empleadoId;
    public $fechaInicio;
    public $fechaFin;
    public $sueldo;

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
                },
            ],
            'sueldo' => 'required|numeric|min:0',
        ]);

        try {
            PlanSueldo::create([
                'plan_empleado_id' => $this->empleadoId,
                'fecha_inicio' => $this->fechaInicio,
                'fecha_fin' => $this->fechaFin ?: null,
                'sueldo' => $this->sueldo,
                'creado_por' => auth()->id(),
            ]);

            $this->alert('success', 'Sueldo registrado correctamente');
            $this->reset(['fechaInicio', 'fechaFin', 'sueldo']);
            $this->dispatch('empleadoActualizado');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function eliminarSueldo($sueldoId)
    {
        try {
            app(PlanSueldoServicio::class)->eliminar($sueldoId);
            $this->alert('success', 'Sueldo eliminado correctamente');
            $this->dispatch('empleadoActualizado');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.planilla.empleado.empleado-sueldos-tab-component', [
            'sueldos' => PlanSueldo::with('creador')->where('plan_empleado_id', $this->empleadoId)->orderByDesc('fecha_inicio')->get(),
        ]);
    }
}
