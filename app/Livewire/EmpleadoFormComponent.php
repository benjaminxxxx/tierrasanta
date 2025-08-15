<?php

namespace App\Livewire;

use App\Models\Cargo;
use App\Models\Contrato;
use App\Models\DescuentoSP;
use App\Models\Grupo;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use DB;
use Exception;
use Livewire\Component;
use App\Models\Empleado;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmpleadoFormComponent extends Component
{
    use LivewireAlert;
    public $empleadoId;
    public $isFormOpen = false;
    public $nombres;
    public $apellido_paterno;
    public $apellido_materno;
    public $documento;
    public $descuentos;
    public $cargos;
    public $grupos;
    public $fecha_ingreso;
    public $fecha_nacimiento;
    public $cargo_id;
    public $descuento_sp_id;
    public $genero;
    public $salario;
    public $grupo_codigo;
    public $compensacion_vacacional;
    public $esta_jubilado;
    public $tipo_planilla;
    public $contratos = [];
    public $mostrarFormularioContrato = false;
    #region contrato
    public $tipo_contrato;
    public $fecha_inicio;
    public $fecha_fin;
    public $sueldo;
    public $cargo_codigo;
    public $modalidad_pago = 'mensual';
    public $motivo_despido;
    #endregion
    protected $listeners = ['EditarEmpleado'];

    public function mount()
    {
        $this->descuentos = DescuentoSP::all();
        $this->cargos = Cargo::all();
        $this->grupos = Grupo::all();
    }

    public function EditarEmpleado($code)
    {
        $this->resetForm();
        $empleado = Empleado::with(['contratos'])->where('code', $code)->first();
        if ($empleado) {


            $this->empleadoId = $empleado->id;
            $this->nombres = $empleado->nombres;
            $this->apellido_paterno = $empleado->apellido_paterno;
            $this->apellido_materno = $empleado->apellido_materno;
            $this->documento = $empleado->documento;
            $this->fecha_ingreso = $empleado->fecha_ingreso;
            $this->fecha_nacimiento = $empleado->fecha_nacimiento;
            $this->genero = $empleado->genero;
            $this->obtenerContratos();
            $this->isFormOpen = true;
        }
    }
    public function store()
    {
        // Validar solo el campo 'nombres' con un mensaje personalizado
        $datosValidados = [
            'nombres' => 'required|string',
            'documento' => [
                'required',
                'string',
                Rule::unique('empleados', 'documento')->ignore($this->empleadoId),
            ],
            'fecha_ingreso' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];
        if (!$this->empleadoId) {
            $datosValidados = [
                'nombres' => 'required|string',
                'documento' => [
                    'required',
                    'string',
                    Rule::unique('empleados', 'documento')->ignore($this->empleadoId),
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
                $empleado = Empleado::find($this->empleadoId);
                if ($empleado) {
                    $empleado->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                $data['code'] = Str::random(15);
                $empleado = Empleado::create($data);
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
            $this->isFormOpen = false;
            $this->resetForm();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function abrirFormularioNuevoEmpleado()
    {
        $this->resetForm();
        $this->isFormOpen = true;
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->dispatch('reset-tab');
        $this->reset([
            'empleadoId',
            'contratos',
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'documento',
            'fecha_ingreso',
            'fecha_nacimiento',
            'genero',

            'tipo_contrato',

            'fecha_inicio',
            'descuento_sp_id',
            'grupo_codigo',

            'cargo_codigo',
            'tipo_planilla',
            'sueldo',

            'compensacion_vacacional',
            'modalidad_pago',
            'esta_jubilado',

            'fecha_fin',
            'motivo_despido'
        ]);
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
        $this->contratos = Contrato::where('empleado_id', $this->empleadoId)
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
        return view('livewire.empleado-form-component');
    }
}
