<?php

namespace App\Livewire\GestionPlanilla\AdministrarFamiliares;

use App\Models\PlanFamiliar;
use App\Models\PlanEmpleado;
use Livewire\Component;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class FamiliaresPlanillaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioFamiliaresPlanilla = false;
    public $nombre_empleado;
    public $empleadoId;
    public $nombres;
    public $fecha_nacimiento;
    public $documento;
    public $esta_estudiando;
    public $familiares;
    public $asignacionId;
    protected $listeners = ['agregarFamiliarEmpleado', 'confirmarEliminarHijo'];

    public function agregarFamiliarEmpleado($id)
    {
        $this->resetErrorBag();
        $empleado = PlanEmpleado::find( $id);
        if ($empleado) {
            $this->empleadoId = $empleado->id;
            $this->nombre_empleado = $empleado->nombreCompleto;
            $this->mostrarFormularioFamiliaresPlanilla = true;
        } else {
            $this->alert('error', 'El empleado ya no existe');
        }
    }
    public function closeForm()
    {
        $this->mostrarFormularioFamiliaresPlanilla = false;
    }
    public function guardarRegistroFamiliarPlanilla()
    {
        $this->validate([
            'nombres' => 'required|string',
            'fecha_nacimiento' => 'required|date',
            'documento' => [
                'required',
                'string',
                Rule::unique('plan_familiares')
                    ->where(fn($query) => $query->where('plan_empleado_id', $this->empleadoId)),
            ],
        ], [
            'nombres.required' => 'El Nombre es obligatorio.',
            'documento.required' => 'El Documento es obligatorio.',
            'documento.unique' => 'El Documento ya está en uso para este empleado.',
            'fecha_nacimiento.required' => 'La fecha de Nacimiento es obligatoria.',
            'fecha_nacimiento.date' => 'La fecha de Nacimiento no es válida.',
        ]);
        try {
            $data = [
                'nombres' => mb_strtoupper($this->nombres),
                'plan_empleado_id' => $this->empleadoId,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'documento' => $this->documento,
                'esta_estudiando' => $this->esta_estudiando ?? 0,
            ];

            PlanFamiliar::create($data);
            $this->dispatch("HijoRegistrado");
            $this->alert('success', 'Registro actualizado exitosamente.');
            $this->reset(['nombres', 'esta_estudiando', 'fecha_nacimiento', 'documento']);

            $this->resetErrorBag();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function confirmarEliminacion($id)
    {
        $this->asignacionId = $id;

        $this->alert('question', '¿Está seguro(a) que desea eliminar el Hijo del Empleado?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminarHijo',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function confirmarEliminarHijo()
    {
        if ($this->asignacionId) {
            $asignacion = PlanFamiliar::find($this->asignacionId);
            if ($asignacion) {
                $asignacion->delete();
                $this->dispatch("HijoRegistrado");
            }
        }
    }
    public function render()
    {
        $this->familiares = PlanFamiliar::where('plan_empleado_id', $this->empleadoId)->get();
        return view('livewire.gestion-planilla.administrar-familiares.familiar-planilla-form');
    }
}
