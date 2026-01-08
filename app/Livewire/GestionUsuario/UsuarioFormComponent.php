<?php

namespace App\Livewire\GestionUsuario;

use App\Services\Sistema\AccesoServicio;
use App\Services\Sistema\UsuarioServicio;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class UsuarioFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $userId;
    public $name;
    public $email;
    public $password;
    public $rolesDisponibles;
    public $rolesSeleccionados = [];

    protected $listeners = ['CrearUsuario', 'EditarUsuario'];

    public function mount()
    {
        $this->rolesDisponibles = AccesoServicio::obtenerRoles();
    }

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
            try {
                $usuario = UsuarioServicio::obtenerUsuarioPorId($id);
                if ($usuario) {
                    $this->name = $usuario->name;
                    $this->email = $usuario->email;
                    $this->rolesSeleccionados = $usuario->roles?->pluck('name')->toArray()??[];
                }
            } catch (\Throwable $th) {
                $this->alert('error', 'Error al cargar usuario: ' . $th->getMessage());
            }
        }
    }

    public function resetFields()
    {
        $this->userId = false;
        $this->name = null;
        $this->email = null;
        $this->password = null;
        $this->rolesSeleccionados = [];
        $this->resetErrorBag();
    }

    public function crear()
    {
        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if (!$this->userId || !empty($this->password)) {
                $data['password'] = $this->password;
            }

            // Validar
            $datosValidados = UsuarioServicio::validarDatos($data, $this->userId);

            // Guardar
            UsuarioServicio::guardarUsuario($this->userId, $datosValidados, $this->rolesSeleccionados);

            $this->dispatch("ActualizarUsuarios");
            $this->cerrarMostrarFormulario();
            $this->alert("success", "Registros actualizados correctamente");
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
        return view('livewire.gestion-usuario.usuario-form-component');
    }
}
