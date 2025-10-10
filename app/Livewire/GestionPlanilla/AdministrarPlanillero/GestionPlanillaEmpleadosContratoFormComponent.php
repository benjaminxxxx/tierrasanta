<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;

use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Traits\ListasComunes\ConGrupoPlanilla;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionPlanillaEmpleadosContratoFormComponent extends Component
{
    use LivewireAlert, ConGrupoPlanilla;
    public $empleadoId;
    public $mostrarFormularioEmpleadoContrato = false;

    protected $listeners = ['abrirFormularioRegistroEmpleadoContrato'];

    public function abrirFormularioRegistroEmpleadoContrato()
    {
        $this->mostrarFormularioEmpleadoContrato = true;
    }

    public function guardarContrato()
    {
        if (!$this->empleadoId) {
            $this->alert('error', 'Debe seleccionar un empleado primero');
            return;
        }

        // 1️⃣ Validaciones básicas
        $this->validate([
            'fecha_inicio' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (date('d', strtotime($value)) != 1) {
                        $fail('La fecha de inicio debe ser siempre el día 1.');
                    }
                }
            ],
            'tipo_planilla' => 'required',
            'sueldo' => 'required|numeric|min:0'
        ]);

        try {
            $data = [
                'tipo_contrato' => $this->tipo_contrato ?? 'indefinido',
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
                'sueldo' => $this->sueldo,
                'cargo_codigo' => $this->cargo_codigo,
                'grupo_codigo' => $this->grupo_codigo,
                'compensacion_vacacional' => $this->compensacion_vacacional ?? 0,
                'tipo_planilla' => $this->tipo_planilla,
                'descuento_sp_id' => $this->descuento_sp_id,
                'esta_jubilado' => $this->esta_jubilado ?? 0,
                'modalidad_pago' => $this->modalidad_pago,
                'motivo_despido' => $this->motivo_despido ?? null,
            ];

            app(ContratoServicio::class)->registrarContrato($this->empleadoId, $data);

            $this->alert('success', 'Contrato registrado correctamente');
            $this->mostrarFormularioContrato = false;
            $this->reset(['fecha_inicio', 'fecha_fin', 'sueldo', 'cargo_codigo', 'grupo_codigo', 'compensacion_vacacional', 'tipo_planilla', 'descuento_sp_id', 'esta_jubilado', 'modalidad_pago', 'motivo_despido']);
            $this->obtenerContratos();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
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

        // Obtener todos los contratos
        $this->contratos = PlanContrato::where('empleado_id', $this->empleadoId)
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Si hay contratos, tomar el último y asignar valores iniciales
        $ultimoContrato = $this->contratos->first();
        if ($ultimoContrato) {
            $this->tipo_contrato = $ultimoContrato->tipo_contrato ?? 'indefinido';
            $this->fecha_inicio = now()->format('Y-m-d'); // nueva fecha por defecto
            $this->fecha_fin = null;
            $this->sueldo = $ultimoContrato->sueldo;
            $this->cargo_codigo = $ultimoContrato->cargo_codigo;
            $this->grupo_codigo = $ultimoContrato->grupo_codigo;
            $this->compensacion_vacacional = $ultimoContrato->compensacion_vacacional ?? 0;
            $this->tipo_planilla = $ultimoContrato->tipo_planilla;
            $this->descuento_sp_id = $ultimoContrato->descuento_sp_id;
            $this->esta_jubilado = $ultimoContrato->esta_jubilado ?? 0;
            $this->modalidad_pago = $ultimoContrato->modalidad_pago;
            $this->motivo_despido = null; // nuevo contrato → sin motivo de despido
        }
    }


    #endregion
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados-contrato-form');
    }
}
