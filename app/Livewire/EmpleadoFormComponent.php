<?php

namespace App\Livewire;

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
        ];
    }

    protected $messages = [
        'nombres.required' => 'El Nombre es obligatorio.',
        'documento.required' => 'El Documento es obligatorio.',
        'documento.unique' => 'El Documento ya está en uso.',
    ];
    public function EditarEmpleado($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $this->empleadoId = $empleado->id;
            $this->nombres = $empleado->nombres;
            $this->apellido_paterno = $empleado->apellido_paterno;
            $this->apellido_materno = $empleado->apellido_materno;
            $this->documento = $empleado->documento;
            $this->CrearEmpleado();
        }
    }
    public function store()
    {
        // Validar solo el campo 'nombres' con un mensaje personalizado
        $this->validate();

        try {
            $data = [
                'nombres' => mb_strtoupper($this->nombres),
                'apellido_paterno' => mb_strtoupper($this->apellido_paterno),
                'apellido_materno' => mb_strtoupper($this->apellido_materno),
                'documento' => $this->documento,
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
            $this->reset(['nombres', 'apellido_paterno', 'apellido_materno', 'documento']);
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
