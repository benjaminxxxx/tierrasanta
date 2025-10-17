<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Traits\ListasComunes\ConGrupoPlanilla;
use Exception;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionPlanillaEmpleadosContratoFormComponent extends Component
{
    use LivewireAlert, ConGrupoPlanilla;
    public $empleadoId;
    public $contratos = [];
    public $tipoContrato = 'indefinido';
    public $fechaInicio;
    public $cargoCodigo;
    public $grupoCodigo;
    public $compensacionVacacional;
    public $tipoPlanilla;
    public $planSpCodigo;
    public $estaJubilado;
    public $modalidadPago;
    public $mostrarFormularioEmpleadoContrato = false;

    protected $listeners = ['abrirFormularioRegistroEmpleadoContrato'];

    public function abrirFormularioRegistroEmpleadoContrato($uuid)
    {
        $empleado = PlanEmpleado::where('uuid', $uuid)->first();
        if (!$empleado) {
            throw new Exception('El empleado ya no existe');
        }
        $this->empleadoId = $empleado->id;
        $this->mostrarFormularioEmpleadoContrato = true;
        $this->obtenerContratos();
    }

    public function guardarContrato()
    {
        
        if (!$this->empleadoId) {
            $this->alert('error', 'Debe seleccionar un empleado primero');
            return;
        }

        try {
            $data = [
                'tipo_contrato' => $this->tipoContrato ?? 'indefinido',
                'fecha_inicio' => $this->fechaInicio,
                'cargo_codigo' => $this->cargoCodigo,
                'grupo_codigo' => $this->grupoCodigo,
                'compensacion_vacacional' => $this->compensacionVacacional ?? 0,
                'tipo_planilla' => $this->tipoPlanilla,
                'plan_sp_codigo' => $this->planSpCodigo,
                'esta_jubilado' => $this->estaJubilado ?? 0,
                'modalidad_pago' => $this->modalidadPago,
            ];

            app(ContratoServicio::class)->registrarContrato($this->empleadoId, $data);

            $this->alert('success', 'Contrato registrado correctamente');
            $this->mostrarFormularioContrato = false;
            $this->resetearForm();
            $this->obtenerContratos();

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error: ' . $th->getMessage());
        }
    }
    public function eliminarContrato($contratoId)
    {
        try {

            app(ContratoServicio::class)
                ->eliminarContratoPorId($contratoId);

            $this->alert('success', 'Contrato eliminado correctamente');
            $this->obtenerContratos();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }

    }
    public function obtenerContratos()
    {
        if (!$this->empleadoId) {
            $this->contratos = [];
            return;
        }

        $this->contratos = PlanContrato::where('plan_empleado_id', $this->empleadoId)
            ->orderBy('fecha_inicio', 'asc')
            ->get();
    }
    private function resetearForm()
    {
        $this->resetErrorBag();
        $this->reset([
            'tipoContrato',
            'fechaInicio',
            'cargoCodigo',
            'grupoCodigo',
            'compensacionVacacional',
            'tipoPlanilla',
            'planSpCodigo',
            'estaJubilado',
            'modalidadPago',
        ]);
    }
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados-contrato-form');
    }
}
