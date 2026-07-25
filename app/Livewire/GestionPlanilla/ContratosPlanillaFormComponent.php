<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Carbon\Carbon;

class ContratosPlanillaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioContrato = false;

    // Paso 1: selección de empleado
    public ?int $filtroEmpleadoId = null;
    public ?string $empleadoNombre = null;

    // Paso 2: historial + formulario
    public $historial = [];
    public $contratoVigente = null;

    public bool $mostrarForm = false;
    public bool $esEdicion = false;
    public ?int $contratoId = null;

    public $tipo_contrato = '';
    public $fecha_inicio = '';
    public $grupo_codigo = '';
    public $compensacion_vacacional = '';
    public $tipo_planilla = '';
    public $plan_sp_codigo = '';
    public $esta_jubilado = 0;
    public $modalidad_pago = '';
    public $fecha_fin_prueba = '';

    // Panel de finalizar (por contrato)
    public ?int $contratoAFinalizarId = null;
    public $datosCierre = ['fecha_fin' => '', 'motivo_cese_sunat' => '', 'comentario_cese' => ''];

    protected $listeners = ['abrirFormularioRegistroContrato' => 'seleccionarEmpleado'];

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
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}"),
            ])
            ->toArray();
    }

    public function updatedFiltroEmpleadoId($value): void
    {
        if ($value) {
            $this->cargarEmpleado((int) $value);
        } else {
            $this->limpiarSeleccion();
        }
    }

    // Permite abrir el componente ya con un empleado preseleccionado (dispatch externo)
    public function seleccionarEmpleado($empleadoId = null): void
    {
        $this->limpiarSeleccion();
        $this->mostrarFormularioContrato = true;
        if ($empleadoId) {
            $this->filtroEmpleadoId = $empleadoId;
            $this->cargarEmpleado((int) $empleadoId);
        }

    }

    protected function cargarEmpleado(int $empleadoId): void
    {
        $empleado = PlanEmpleado::find($empleadoId);

        if (!$empleado) {
            $this->alert('error', 'Empleado inexistente.');
            return;
        }

        $this->filtroEmpleadoId = $empleado->id;
        $this->empleadoNombre = trim("{$empleado->nombres} {$empleado->apellido_paterno} {$empleado->apellido_materno}");

        $this->cargarHistorial();
        $this->cerrarForm();
    }

    protected function cargarHistorial(): void
    {
        $servicio = app(ContratoServicio::class);
        $this->historial = $servicio->historial($this->filtroEmpleadoId);
        $this->contratoVigente = $servicio->contratoVigente($this->filtroEmpleadoId);
    }

    public function limpiarSeleccion(): void
    {
        $this->reset(['filtroEmpleadoId', 'empleadoNombre', 'historial', 'contratoVigente']);
        $this->cerrarForm();
    }

    public function nuevoContrato(): void
    {
        if ($this->contratoVigente) {
            $this->alert('error', 'El empleado ya tiene un contrato vigente. Finalícelo antes de crear uno nuevo.');
            return;
        }

        $this->resetFormulario();
        $this->esEdicion = false;
        $this->mostrarForm = true;
    }

    public function editarContrato(int $id): void
    {
        $contrato = PlanContrato::find($id);

        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado.');
            return;
        }

        $this->contratoId = $contrato->id;
        $this->tipo_contrato = $contrato->tipo_contrato;
        $this->fecha_inicio = $contrato->fecha_inicio->format('Y-m-d');
        $this->grupo_codigo = $contrato->grupo_codigo;
        $this->compensacion_vacacional = $contrato->compensacion_vacacional;
        $this->tipo_planilla = $contrato->tipo_planilla;
        $this->plan_sp_codigo = $contrato->plan_sp_codigo;
        $this->esta_jubilado = $contrato->esta_jubilado ?? 0;
        $this->modalidad_pago = $contrato->modalidad_pago;
        $this->fecha_fin_prueba = $contrato->fecha_fin_prueba ? $contrato->fecha_fin_prueba->format('Y-m-d') : '';

        $this->esEdicion = true;
        $this->mostrarForm = true;
    }

    public function guardarContrato(ContratoServicio $servicio): void
    {
        $this->grupo_codigo = blank($this->grupo_codigo) ? null : $this->grupo_codigo;
        $this->plan_sp_codigo = blank($this->plan_sp_codigo) ? null : $this->plan_sp_codigo;

        $data = [
            'plan_empleado_id' => $this->filtroEmpleadoId,
            'tipo_contrato' => $this->tipo_contrato,
            'fecha_inicio' => $this->fecha_inicio,
            'grupo_codigo' => $this->grupo_codigo,
            'compensacion_vacacional' => $this->compensacion_vacacional !== '' ? (float) $this->compensacion_vacacional : null,
            'tipo_planilla' => $this->tipo_planilla,
            'plan_sp_codigo' => $this->plan_sp_codigo,
            'esta_jubilado' => (bool) $this->esta_jubilado,
            'modalidad_pago' => $this->modalidad_pago,
            'fecha_fin_prueba' => $this->fecha_fin_prueba ? Carbon::parse($this->fecha_fin_prueba) : null,
        ];

        try {
            $servicio->guardarContrato($data, $this->esEdicion ? $this->contratoId : null);

            $this->alert('success', $this->esEdicion ? 'Contrato actualizado.' : 'Contrato creado.');
            $this->cargarHistorial();
            $this->cerrarForm();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function abrirFinalizar(int $contratoId): void
    {
        $this->contratoAFinalizarId = $contratoId;
        $this->datosCierre = ['fecha_fin' => '', 'motivo_cese_sunat' => '', 'comentario_cese' => ''];
    }

    public function confirmarFinalizar(ContratoServicio $servicio): void
    {
        $this->validate([
            'datosCierre.fecha_fin' => ['required', 'date'],
            'datosCierre.motivo_cese_sunat' => ['required', 'string'],
        ]);

        try {
            $servicio->finalizarContrato($this->contratoAFinalizarId, $this->datosCierre);

            $this->alert('success', 'Contrato finalizado.');
            $this->contratoAFinalizarId = null;
            $this->cargarHistorial();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function reabrirContrato(int $contratoId, ContratoServicio $servicio): void
    {
        try {
            $servicio->reabrirContrato($contratoId);

            $this->alert('success', 'Contrato reaperturado. Ya puede editarlo.');
            $this->cargarHistorial();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function cerrarForm(): void
    {
        $this->mostrarForm = false;
        $this->contratoAFinalizarId = null;
        $this->resetFormulario();
    }

    protected function resetFormulario(): void
    {
        $this->reset([
            'contratoId',
            'tipo_contrato',
            'fecha_inicio',
            'grupo_codigo',
            'compensacion_vacacional',
            'tipo_planilla',
            'plan_sp_codigo',
            'esta_jubilado',
            'modalidad_pago',
            'fecha_fin_prueba',
        ]);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.gestion-planilla.contratos-planilla-form-component');
    }
}