<?php

namespace App\Livewire\GestionPlanilla\DerechoHabiente;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\PlanEmpleado;
use App\Models\EmpleadoDerechoHabiente;
use App\Domain\DerechoHabiente\DerechoHabienteService;

class DerechoHabienteWizardComponent extends Component
{
    use LivewireAlert;

    public bool $show = false;
    public string $paso = 'seleccionar'; // seleccionar | gestionar

    public ?int $empleadoId = null;
    public string $empleadoNombre = '';

    public array $vinculosExistentes = [];
    public string $rolEmpleado = 'padre';

    protected $listeners = [
        'abrirDerechoHabienteWizard' => 'abrir',
        'derechoHabienteGuardado' => 'onGuardado',
    ];

    /**
     * @param int|null $empleadoId Si viene null (botón general de la lista), arranca en 'seleccionar'.
     *                              Si viene con id (botón "Familiares" del módulo Empleados), salta directo a 'gestionar'.
     */
    public function abrir(?int $empleadoId = null)
    {
        $this->reset(['vinculosExistentes']);
        $this->rolEmpleado = 'padre';

        if ($empleadoId) {
            $this->seleccionarEmpleado($empleadoId);
        } else {
            $this->empleadoId = null;
            $this->empleadoNombre = '';
            $this->paso = 'seleccionar';
        }

        $this->show = true;
    }

    public function getEmpleados($search)
    {
        $query = PlanEmpleado::orderBy('nombres');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('documento', 'like', "%{$search}%");
            });
        }

        return $query->limit(10)->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}"),
            ])
            ->toArray();
    }

    /** Se dispara solo del select-dropdown, cuando el paso es 'seleccionar'. */
    public function updatedEmpleadoId($value)
    {
        if ($this->paso === 'seleccionar' && $value) {
            $this->seleccionarEmpleado((int) $value);
        }
    }

    public function seleccionarEmpleado(int $empleadoId)
    {
        $empleado = PlanEmpleado::findOrFail($empleadoId);

        $this->empleadoId = $empleadoId;
        $this->empleadoNombre = $empleado->nombreCompleto;
        $this->paso = 'gestionar';

        $this->cargarVinculosExistentes();
    }

    public function cambiarEmpleado()
    {
        $this->empleadoId = null;
        $this->empleadoNombre = '';
        $this->vinculosExistentes = [];
        $this->paso = 'seleccionar';
    }

    private function cargarVinculosExistentes(): void
    {
        $this->vinculosExistentes = EmpleadoDerechoHabiente::with('derechoHabiente')
            ->where('empleado_id', $this->empleadoId)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'nombres' => $v->derechoHabiente->nombres,
                'documento' => $v->derechoHabiente->documento,
                'tipo' => $v->derechoHabiente->tipo,
                'rol' => $v->rol,
                'edad' => $v->derechoHabiente->edad,
            ])
            ->toArray();
    }

    public function agregarDerechoHabiente()
    {
        $this->dispatch('abrirDerechoHabienteFormNuevo',
            empleadoId: $this->empleadoId,
            rol: $this->rolEmpleado,
        );
    }

    public function editarVinculo(int $vinculoId)
    {
        $this->dispatch('abrirDerechoHabienteForm', vinculoId: $vinculoId);
    }

    public function confirmarEliminacion(int $vinculoId)
    {
        $this->confirm('¿Está seguro que desea desvincular este registro?', [
            'onConfirmed' => 'eliminacionConfirmar',
            'data' => ['id' => $vinculoId],
        ]);
    }

    public function eliminacionConfirmar(array $data)
    {
        try {
            app(DerechoHabienteService::class)->eliminarVinculo((int) $data['id']);
            $this->alert('success', 'Registro desvinculado');
            $this->dispatch('derechoHabienteGuardado');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    /** El Form dispara este evento al guardar/eliminar. Si el wizard está abierto en 'gestionar', refresca su lista. */
    public function onGuardado()
    {
        if ($this->show && $this->paso === 'gestionar' && $this->empleadoId) {
            $this->cargarVinculosExistentes();
        }
    }

    public function cerrar()
    {
        $this->reset(['show', 'paso', 'empleadoId', 'empleadoNombre', 'vinculosExistentes']);
        $this->rolEmpleado = 'padre';
    }

    public function render()
    {
        return view('livewire.gestion-planilla.derecho-habiente.derecho-habiente-wizard-component');
    }
}