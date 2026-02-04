<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Carbon\Carbon;

class ContratosPlanillaFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $esEdicion = false;
    public $contratoId = null;
    public $contratoAFinalizar;

    public $plan_empleado_id = '';
    public $tipo_contrato = '';
    public $fecha_inicio = '';
    public $cargo_codigo = '';
    public $grupo_codigo = '';
    public $compensacion_vacacional = '';
    public $tipo_planilla = '';
    public $plan_sp_codigo = '';
    public $esta_jubilado = 0;
    public $modalidad_pago = '';
    public $fecha_fin_prueba = '';
    public $empleados = [];
    public $contrato;
    public $contratosAbiertos = []; // Para el foreach en la vista
    public $datosCierre = [];

    protected $listeners = ['nuevoContrato', 'editarContrato', 'renovarContrato'];
    public function mount()
    {
        $this->empleados = EmpleadoServicio::cargarSearchableEmpleadosPlanilla();
    }
    public function updatedPlanEmpleadoId($value)
    {
        if ($value) {
            // Buscamos contratos activos del empleado seleccionado
            $this->contratosAbiertos = PlanContrato::where('plan_empleado_id', $value)
                ->where('estado', 'activo')
                ->get();

            // Inicializamos los inputs para cada contrato abierto
            foreach ($this->contratosAbiertos as $contrato) {
                $this->datosCierre[$contrato->id] = [
                    'fecha_fin' => '',
                    'motivo_cese_sunat' => '',
                    'comentario_cese' => ''
                ];
            }
        } else {
            $this->contratosAbiertos = [];
            $this->datosCierre = [];
        }
    }

    public function nuevoContrato()
    {
        $this->resetFormulario();
        $this->esEdicion = false;
        $this->mostrarFormulario = true;
    }

    public function editarContrato($id)
    {
        $this->resetFormulario();
        $contrato = PlanContrato::find($id);

        $this->contrato = $contrato;
        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado');
            return;
        }
        $this->contratoId = $contrato->id;
        $this->plan_empleado_id = $contrato->plan_empleado_id;
        $this->tipo_contrato = $contrato->tipo_contrato;
        $this->fecha_inicio = $contrato->fecha_inicio->format('Y-m-d');
        $this->cargo_codigo = $contrato->cargo_codigo;
        $this->grupo_codigo = $contrato->grupo_codigo;
        $this->compensacion_vacacional = $contrato->compensacion_vacacional;
        $this->tipo_planilla = $contrato->tipo_planilla;
        $this->plan_sp_codigo = $contrato->plan_sp_codigo;
        $this->esta_jubilado = $contrato->esta_jubilado ?? 0;
        $this->modalidad_pago = $contrato->modalidad_pago;
        $this->fecha_fin_prueba = $contrato->fecha_fin_prueba ? $contrato->fecha_fin_prueba->format('Y-m-d') : '';


        $this->esEdicion = true;
        $this->mostrarFormulario = true;
    }

    public function renovarContrato($id)
    {
        $contrato = PlanContrato::find($id);

        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado');
            return;
        }

        $this->contratoId = $contrato->id;
        $this->plan_empleado_id = $contrato->plan_empleado_id;
        $this->tipo_contrato = $contrato->tipo_contrato;
        $this->fecha_inicio = now()->format('Y-m-d');
        $this->cargo_codigo = $contrato->cargo_codigo;
        $this->grupo_codigo = $contrato->grupo_codigo;
        $this->compensacion_vacacional = $contrato->compensacion_vacacional;
        $this->tipo_planilla = $contrato->tipo_planilla;
        $this->plan_sp_codigo = $contrato->plan_sp_codigo;
        $this->esta_jubilado = $contrato->esta_jubilado ?? 0;
        $this->modalidad_pago = $contrato->modalidad_pago;
        $this->fecha_fin_prueba = '';

        $this->esEdicion = false;
        $this->mostrarFormulario = true;
    }

    public function guardarContrato(ContratoServicio $servicio)
    {
        $data = [
            'plan_empleado_id' => $this->plan_empleado_id,
            'tipo_contrato' => $this->tipo_contrato,
            'fecha_inicio' => $this->fecha_inicio,
            'cargo_codigo' => $this->cargo_codigo ?? null,
            'grupo_codigo' => $this->grupo_codigo,
            'compensacion_vacacional' => $this->compensacion_vacacional !== null
                ? (float) $this->compensacion_vacacional
                : null,
            'tipo_planilla' => $this->tipo_planilla,
            'plan_sp_codigo' => $this->plan_sp_codigo,
            'esta_jubilado' => (bool) $this->esta_jubilado,
            'modalidad_pago' => $this->modalidad_pago,
            'fecha_fin_prueba' => $this->fecha_fin_prueba ? Carbon::parse($this->fecha_fin_prueba) : null,
        ];


        try {
            DB::transaction(function () use ($servicio, $data) {

                // 1. Si hay contratos abiertos, los finalizamos primero
                if (!$this->esEdicion && count($this->contratosAbiertos) > 0) {
                    foreach ($this->datosCierre as $id => $valores) {
                        // Validar que los datos de cierre estén llenos
                        if (empty($valores['fecha_fin']) || empty($valores['motivo_cese_sunat'])) {
                            throw new \Exception("Debe completar la fecha y motivo de cese de los contratos anteriores.");
                        }

                        $servicio->finalizarContrato($id, $valores);
                    }
                }

                // 2. Proceder con el guardado o edición del contrato principal
                $servicio->guardarContrato($data, $this->esEdicion ? $this->contratoId : null);
            });

            $mensaje = $this->esEdicion ? 'Actualizado correctamente' : 'Creado correctamente (Contratos previos cerrados)';
            $this->alert('success', $mensaje);

            $this->dispatch('contratoActualizado');
            $this->cerrarFormulario();

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function cerrarFormulario()
    {
        $this->mostrarFormulario = false;
        $this->resetFormulario();
    }

    private function resetFormulario()
    {
        $this->contratosAbiertos = []; // Para el foreach en la vista
        $this->datosCierre = [];
        $this->reset([
            'contrato',
            'contratoId',
            'plan_empleado_id',
            'tipo_contrato',
            'fecha_inicio',
            'cargo_codigo',
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
        $empleados = PlanEmpleado::orderBy('created_at', 'desc')
            ->get();

        return view('livewire.gestion-planilla.contratos-planilla-form-component', [
            'empleados' => $empleados,
        ]);
    }
}
