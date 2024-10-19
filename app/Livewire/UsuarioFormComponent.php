<?php

namespace App\Livewire;

use App\Actions\Fortify\PasswordValidationRules;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class UsuarioFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $userId;
    public $name;
    public $email;
    public $password;
    protected $listeners = ['CrearUsuario', 'EditarUsuario'];

    public function CrearUsuario()
    {
        $this->mostrarFormulario = true;
        $this->resetFields();
    }
    public function EditarUsuario($id)
    {
        $this->CrearUsuario();
        $this->userId = $id;
        if ($this->userId) {
            $usuario = User::find($id);
            if ($usuario) {
                $this->name = $usuario->name;
                $this->email = $usuario->email;
            }
        }
    }
    public function resetFields()
    {
        $this->userId = false;
        $this->name = null;
        $this->email = null;
        $this->password = null;
        $this->resetErrorBag();
        
    }
    public function crear()
    {
        // Preparar los datos
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        // Si estamos creando un usuario, añadimos la contraseña
        if (!$this->userId) {
            $data['password'] = $this->password;
        }

        // Validar los datos con reglas específicas para la creación y edición
        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->userId) // Ignorar el email actual del usuario al editar
            ],
            'password' => $this->userId ? [] : ['required', 'string', 'min:2', 'max:255'], // Si no hay userId (crear), contraseña es obligatoria
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.unique' => 'El correo ya existe para otro usuario.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo debe ser válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 2 caracteres.',
            'password.max' => 'La contraseña no puede tener más de 255 caracteres.',
        ])->validate();

        try {
            // Crear o actualizar el usuario
            $dataNueva = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            // Solo agregar la contraseña si está presente (en creación o edición voluntaria)
            if (!$this->userId || !empty($this->password)) {
                $dataNueva['password'] = Hash::make($this->password);
            }

            // Crear o actualizar el usuario
            if ($this->userId) {
                // Actualizar el usuario
                User::where('id', $this->userId)->update($dataNueva);
            } else {
                // Crear el usuario
                User::create($dataNueva);
            }

            $this->dispatch("ActualizarUsuarios");
            $this->cerrarMostrarFormulario(); // Cierra el formulario después de guardar

            $this->alert("success", "Registros actualizados");
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }
    public function cerrarMostrarFormulario()
    {
        $this->resetFields();
        $this->mostrarFormulario = false;
    }
    public function render()
    {
        return view('livewire.usuario-form-component');
    }
}
