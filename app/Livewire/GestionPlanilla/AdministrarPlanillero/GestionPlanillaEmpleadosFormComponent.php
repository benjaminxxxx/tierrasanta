<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;

use App\Services\Modulos\Planilla\GestionPlanillaEmpleados;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Traits\ListasComunes\ConGrupoPlanilla;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use App\Models\PlanEmpleado;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GestionPlanillaEmpleadosFormComponent extends Component
{
    use LivewireAlert, ConGrupoPlanilla;
    public $empleadoId;
    public $nombres;
    public $apellido_paterno;
    public $apellido_materno;
    public $documento;
    public $email;
    public $direccion;
    public $genero;
    public $fecha_nacimiento;
    public $fecha_ingreso;

    public $mostrarFormularioEmpleados = false;

    protected $listeners = ['editarEmpleado', 'abrirFormularioNuevoEmpleado'];

    public function mount()
    {

    }

    public function editarEmpleado($uuid)
    {
        $this->resetForm();
        $empleado = app(GestionPlanillaEmpleados::class)->obtenerEmpleadoPorUuid($uuid);
        ;
        if ($empleado) {

            $this->empleadoId = $empleado->id;
            $this->uuid = $empleado->uuid;
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
            $this->comentarios = $empleado->comentarios;
            $this->orden = $empleado->orden;
            $this->mostrarFormularioEmpleados = true;
        }
    }
    public function guardarEmpleado()
    {
        try {
            $datos = [
                'nombres' => $this->nombres,
                'apellido_paterno' => $this->apellido_paterno,
                'apellido_materno' => $this->apellido_materno,
                'documento' => $this->documento,
                'email' => $this->email,
                'direccion' => $this->direccion,
                'genero' => $this->genero,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'fecha_ingreso' => $this->fecha_ingreso,
            ];

            app(GestionPlanillaEmpleados::class)->guardarEmpleado($datos,$this->empleadoId);
            $this->alert('success', 'Los datos fueron guardados correctamente');
            $this->mostrarFormularioEmpleados = false;
            $this->dispatch('empleadoGuardado');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
        return;
        // Validar solo el campo 'nombres' con un mensaje personalizado
        $datosValidados = [
            'nombres' => 'required|string',
            'documento' => [
                'required',
                'string',
                Rule::unique('plan_empleados', 'documento')->ignore($this->empleadoId),
            ],

            'descuento_sp_id' => 'required',
            'fecha_ingreso' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];
        if (!$this->empleadoId) {
            $datosValidados = [
                'nombres' => 'required|string',
                'documento' => [
                    'required',
                    'string',
                    Rule::unique('plan_empleados', 'documento')->ignore($this->empleadoId),
                ],
                'fecha_ingreso' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
                'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before:today'],
                'fecha_inicio' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        if (date('d', strtotime($value)) != 1) {
                            $fail('La fecha de inicio debe ser siempre el día 1.');
                        }
                    }
                ],
                'descuento_sp_id' => 'required',
                'tipo_planilla' => 'required',
                'sueldo' => 'required|numeric|min:0',
            ];
        }

        $this->validate($datosValidados, [
            'nombres.required' => 'El Nombre es obligatorio.',
            'documento.required' => 'El Documento es obligatorio.',
            'documento.unique' => 'El Documento ya está en uso.',
            'fecha_ingreso.date_format' => 'La Fecha de Ingreso debe tener un formato válido (YYYY-MM-DD).',
            'fecha_ingreso.before_or_equal' => 'La Fecha de Ingreso no puede ser futura.',
            'fecha_nacimiento.date_format' => 'La Fecha de Nacimiento debe tener un formato válido (YYYY-MM-DD).',
            'fecha_nacimiento.before' => 'La Fecha de Nacimiento debe ser anterior a hoy.',
        ]);
        if ($this->fecha_ingreso == '') {
            $this->fecha_ingreso = null;
        }
        if ($this->fecha_nacimiento == '') {
            $this->fecha_nacimiento = null;
        }
        try {
            $data = [
                'nombres' => mb_strtoupper($this->nombres),
                'apellido_paterno' => mb_strtoupper($this->apellido_paterno),
                'apellido_materno' => mb_strtoupper($this->apellido_materno),
                'documento' => $this->documento,
                'genero' => $this->genero,
                'fecha_ingreso' => $this->fecha_ingreso,
                'fecha_nacimiento' => $this->fecha_nacimiento,

            ];

            if ($this->empleadoId) {
                $empleado = PlanEmpleado::find($this->empleadoId);
                if ($empleado) {
                    $empleado->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                $data['code'] = Str::random(15);
                $empleado = PlanEmpleado::create($data);
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

                app(ContratoServicio::class)->registrarContrato($empleado->id, $data);

                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar

            $this->dispatch('EmpleadoRegistrado');
            $this->mostrarFormularioEmpleados = false;
            $this->resetForm();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function abrirFormularioNuevoEmpleado()
    {
        $this->resetForm();
        $this->mostrarFormularioEmpleados = true;
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'documento',
            'email',
            'direccion',
            'genero',
            'fecha_nacimiento',
            'fecha_ingreso',
            'empleadoId'
        );
    }
    #region contrato

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
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados-form');
    }
}
