<?php

namespace App\Livewire\Planilla\Empleado;

use App\Models\PlanContrato;
use App\Services\Planilla\Empleado\ContratoServicio;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pestaña "Contratos" del panel del empleado (reemplaza al modal ContratosPlanillaFormComponent).
 */
class EmpleadoContratosTabComponent extends Component
{
    use LivewireAlert;

    public int $empleadoId;

    public $historial = [];
    public $contratoVigente = null;

    public bool $mostrarForm = false;
    public bool $esEdicion = false;
    public ?int $contratoId = null;

    public $tipo_contrato = '';
    public $fecha_inicio = '';
    public $grupo_codigo = '';
    public $remuneracion_basica = null;
    public $bonificacion = null;
    public $compensacion_vacacional = '';
    public $tipo_planilla = '';
    public $plan_sp_codigo = '';
    public $esta_jubilado = 0;
    public $modalidad_pago = '';
    public $fecha_fin_prueba = '';

    public ?int $contratoAFinalizarId = null;
    public $datosCierre = ['fecha_fin' => '', 'motivo_cese_sunat' => '', 'comentario_cese' => ''];

    public function mount(): void
    {
        $this->cargarHistorial();
    }

    protected function cargarHistorial(): void
    {
        $servicio = app(ContratoServicio::class);
        $this->historial = $servicio->historial($this->empleadoId);
        $this->contratoVigente = $servicio->contratoVigente($this->empleadoId);
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
        $this->remuneracion_basica = $contrato->remuneracion_basica;
        $this->bonificacion = $contrato->bonificacion;
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
        $data = [
            'plan_empleado_id' => $this->empleadoId,
            'tipo_contrato' => $this->tipo_contrato,
            'fecha_inicio' => $this->fecha_inicio,
            'grupo_codigo' => blank($this->grupo_codigo) ? null : $this->grupo_codigo,
            'remuneracion_basica' => $this->remuneracion_basica !== '' ? (float) $this->remuneracion_basica : null,
            'bonificacion' => $this->bonificacion !== '' ? (float) $this->bonificacion : null,
            'compensacion_vacacional' => $this->compensacion_vacacional !== '' ? (float) $this->compensacion_vacacional : null,
            'tipo_planilla' => $this->tipo_planilla,
            'plan_sp_codigo' => blank($this->plan_sp_codigo) ? null : $this->plan_sp_codigo,
            'esta_jubilado' => (bool) $this->esta_jubilado,
            'modalidad_pago' => $this->modalidad_pago,
            'fecha_fin_prueba' => $this->fecha_fin_prueba ? Carbon::parse($this->fecha_fin_prueba) : null,
        ];

        try {
            $servicio->guardarContrato($data, $this->esEdicion ? $this->contratoId : null);

            $this->alert('success', $this->esEdicion ? 'Contrato actualizado.' : 'Contrato creado.');
            $this->cargarHistorial();
            $this->cerrarForm();
            $this->avisarCambios();
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
            $this->avisarCambios();
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
            $this->avisarCambios();
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
            'contratoId', 'tipo_contrato', 'fecha_inicio', 'grupo_codigo', 'compensacion_vacacional', 'tipo_planilla',
            'plan_sp_codigo', 'esta_jubilado', 'modalidad_pago', 'fecha_fin_prueba', 'remuneracion_basica', 'bonificacion',
        ]);
        $this->resetErrorBag();
    }

    /** Refresca el perfil y la lista de empleados (estado de contrato, grupo). */
    private function avisarCambios(): void
    {
        $this->dispatch('empleadoActualizado');
        $this->dispatch('empleadoGuardado');
    }

    public function render()
    {
        return view('livewire.planilla.empleado.empleado-contratos-tab-component');
    }
}
