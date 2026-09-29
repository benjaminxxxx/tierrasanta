<?php

namespace App\Livewire\Planilla\Empleado;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Traits\ListasComunes\ConGrupoPlanilla;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Panel único del empleado: Perfil, Datos, Contratos, Sueldos, Cargos y Derecho habientes.
 * Cada pestaña (salvo Datos) es un componente propio en App\Livewire\GestionPlanilla\Empleado.
 *
 * Se abre con el evento `abrirEmpleado` (id, tab). También escucha los eventos de los modales
 * antiguos para que los botones existentes abran la pestaña correspondiente.
 */
class GestionPlanillaEmpleadosFormComponent extends Component
{
    use LivewireAlert, ConGrupoPlanilla;

    public const TABS = [
        'perfil' => 'Perfil',
        'datos' => 'Datos personales',
        'contratos' => 'Contratos',
        'sueldos' => 'Sueldos',
        'cargos' => 'Cargos',
        'familiares' => 'Derecho habientes',
    ];

    public $empleadoId;
    public string $empleadoNombre = '';
    public string $tab = 'datos';
    public bool $eliminado = false;
    // Se abrió sin empleado (p. ej. "Crear contrato" del panel de contratos): primero se elige uno
    public bool $buscandoEmpleado = false;
    public ?int $empleadoBuscadoId = null;

    public $nombres;
    public $apellido_paterno;
    public $apellido_materno;
    public $documento;
    public $email;
    public $numero;
    public $direccion;
    public $genero;
    public $fecha_nacimiento;
    public $fecha_ingreso;
    public $mostrarFormularioEmpleados = false;

    protected $listeners = [
        'abrirEmpleado',
        'editarEmpleado',
        'abrirFormularioNuevoEmpleado',
        // Eventos de los modales antiguos (ahora pestañas)
        'abrirFormularioRegistroContrato' => 'abrirContratos',
        'nuevoContrato' => 'abrirContratos',
        'renovarContrato' => 'abrirDesdeContrato',
        'abrirFormularioRegistroEmpleadoSueldo' => 'abrirSueldos',
        'abrirFormularioRegistroEmpleadoCargo' => 'abrirCargos',
    ];

    public function abrirEmpleado($id = null, string $tab = 'perfil'): void
    {
        $this->resetForm();
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'perfil';

        if (!$id) {
            $this->buscandoEmpleado = true;
            $this->mostrarFormularioEmpleados = true;
            return;
        }

        $empleado = PlanEmpleado::withTrashed()->find($id);
        if (!$empleado) {
            $this->alert('error', 'El empleado ya no existe.');
            return;
        }

        $this->cargarEmpleado($empleado);
        $this->mostrarFormularioEmpleados = true;
    }

    public function editarEmpleado($id): void
    {
        $this->abrirEmpleado($id, 'datos');
    }

    public function abrirContratos($empleadoId = null): void
    {
        $this->abrirEmpleado($empleadoId, 'contratos');
    }

    public function abrirDesdeContrato($id): void
    {
        $this->abrirEmpleado(PlanContrato::find($id)?->plan_empleado_id, 'contratos');
    }

    public function abrirSueldos($id): void
    {
        $this->abrirEmpleado($id, 'sueldos');
    }

    public function abrirCargos($id): void
    {
        $this->abrirEmpleado($id, 'cargos');
    }

    public function abrirFormularioNuevoEmpleado(): void
    {
        $this->resetForm();
        $this->tab = 'datos';
        $this->mostrarFormularioEmpleados = true;
    }

    /** Buscador del paso "elegir empleado" (x-select-dropdown). */
    public function getEmpleados($search)
    {
        return PlanEmpleado::query()
            ->when($search, fn($q) => $q->where(fn($q) => $q
                ->where('nombres', 'like', "%{$search}%")
                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                ->orWhere('apellido_materno', 'like', "%{$search}%")
                ->orWhere('documento', 'like', "%{$search}%")))
            ->orderBy('apellido_paterno')
            ->limit(10)
            ->get()
            ->map(fn($e) => ['id' => $e->id, 'name' => "{$e->nombreCompleto} — {$e->documento}"])
            ->toArray();
    }

    public function updatedEmpleadoBuscadoId($id): void
    {
        if ($id) {
            $tab = $this->tab;
            $this->abrirEmpleado((int) $id, $tab);
        }
    }

    private function cargarEmpleado(PlanEmpleado $empleado): void
    {
        $this->empleadoId = $empleado->id;
        $this->empleadoNombre = $empleado->nombreCompleto;
        $this->eliminado = $empleado->trashed();
        $this->nombres = $empleado->nombres;
        $this->apellido_paterno = $empleado->apellido_paterno;
        $this->apellido_materno = $empleado->apellido_materno;
        $this->documento = $empleado->documento;
        $this->email = $empleado->email;
        $this->numero = $empleado->numero;
        $this->direccion = $empleado->direccion;
        $this->genero = $empleado->genero;
        $this->fecha_nacimiento = $empleado->fecha_nacimiento;
        $this->fecha_ingreso = $empleado->fecha_ingreso;
    }

    public function guardarEmpleado()
    {
        try {
            $datos = [
                'nombres' => mb_strtoupper($this->nombres),
                'apellido_paterno' => mb_strtoupper($this->apellido_paterno),
                'apellido_materno' => mb_strtoupper($this->apellido_materno),
                'documento' => $this->documento,
                'email' => $this->email,
                'numero' => $this->numero,
                'direccion' => $this->direccion,
                'genero' => $this->genero,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'fecha_ingreso' => $this->fecha_ingreso,
            ];

            $esNuevo = !$this->empleadoId;
            $empleado = app(PlanillaEmpleadoServicio::class)->guardar($datos, $this->empleadoId);

            $this->alert('success', 'Los datos fueron guardados correctamente');
            $this->dispatch('empleadoGuardado');
            $this->dispatch('empleadoActualizado');

            // Empleado nuevo: el panel sigue abierto en Contratos para registrar su contratación
            $this->cargarEmpleado($empleado);
            if ($esNuevo) {
                $this->tab = 'contratos';
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->resetErrorBag();
        $this->reset(
            'nombres', 'apellido_paterno', 'apellido_materno', 'documento', 'email', 'numero', 'direccion',
            'genero', 'fecha_nacimiento', 'fecha_ingreso', 'empleadoId', 'empleadoNombre', 'eliminado',
            'buscandoEmpleado', 'empleadoBuscadoId'
        );
    }

    public function render()
    {
        return view('livewire.planilla.empleado.gestion-planilla-empleados-form');
    }
}
