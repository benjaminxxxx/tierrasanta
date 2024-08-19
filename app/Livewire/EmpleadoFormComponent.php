<?php

namespace App\Livewire;

use App\Models\Cargo;
use App\Models\DescuentoSP;
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
    public $fecha_ingreso;
    public $fecha_nacimiento;
    public $cargo_id;
    public $descuento_sp_id;
    public $genero;
    public $salario;
    protected $listeners = ['EditarEmpleado'];
    protected function rules()
    {
        return [
            'nombres' => 'required|string',
            'documento' => [
                'required',
                'string',
                Rule::unique('empleados', 'documento')->ignore($this->empleadoId),
            ],
            'fecha_ingreso' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];
    }

    protected $messages = [
        'nombres.required' => 'El Nombre es obligatorio.',
        'documento.required' => 'El Documento es obligatorio.',
        'documento.unique' => 'El Documento ya está en uso.',
        'fecha_ingreso.date_format' => 'La Fecha de Ingreso debe tener un formato válido (YYYY-MM-DD).',
        'fecha_ingreso.before_or_equal' => 'La Fecha de Ingreso no puede ser futura.',
        'fecha_nacimiento.date_format' => 'La Fecha de Nacimiento debe tener un formato válido (YYYY-MM-DD).',
        'fecha_nacimiento.before' => 'La Fecha de Nacimiento debe ser anterior a hoy.',
    ];
    public function mount()
    {
        $this->descuentos = DescuentoSP::all();
        $this->cargos = Cargo::all();
    }
    public function EditarEmpleado($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $this->empleadoId = $empleado->id;
            $this->nombres = $empleado->nombres;
            $this->apellido_paterno = $empleado->apellido_paterno;
            $this->apellido_materno = $empleado->apellido_materno;
            $this->documento = $empleado->documento;
            $this->fecha_ingreso = $empleado->fecha_ingreso;
            $this->fecha_nacimiento = $empleado->fecha_nacimiento;
            $this->cargo_id = $empleado->cargo_id;
            $this->descuento_sp_id = $empleado->descuento_sp_id;
            $this->genero = $empleado->genero;
            $this->salario = $empleado->salario;
            $this->CrearEmpleado();
        }
    }
    public function store()
    {
        // Validar solo el campo 'nombres' con un mensaje personalizado
        $this->validate();
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
                'fecha_ingreso' => $this->fecha_ingreso,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'cargo_id' => $this->cargo_id,
                'descuento_sp_id' => $this->descuento_sp_id,
                'genero' => $this->genero,
                'salario' => $this->salario,
            ];

            if ($this->empleadoId) {
                $empleado = Empleado::find($this->empleadoId);
                if ($empleado) {
                    $empleado->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                $data['code'] = Str::random(15);
                Empleado::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            // Limpiar los campos después de guardar
            $this->reset([
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'documento',
                'fecha_ingreso',
                'fecha_nacimiento',
                'descuento_sp_id',
                'genero',
                'salario'
            ]);
            $this->dispatch('EmpleadoRegistrado');
            $this->closeForm();

        } catch (QueryException $e) {
            // Manejar errores de la base de datos
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function CrearEmpleado()
    {
        $this->isFormOpen = true;
    }
    public function closeForm()
    {
        $this->isFormOpen = false;
    }

    public function render()
    {
        return view('livewire.empleado-form-component');
    }
}
