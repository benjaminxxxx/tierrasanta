<?php

namespace App\Livewire\GestionUsuario;

use App\Services\Sistema\AccesoServicio;
use App\Services\Sistema\UsuarioServicio;
use Illuminate\Validation\ValidationException;
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

    // Un único rol seleccionado (string con el nombre del rol)
    public string $rolSeleccionado = '';

    // Nombre del nuevo rol a crear
    public string $nuevoRol = '';

    // Nombres de roles reservados que no pueden crearse manualmente
    protected array $rolesReservados = ['Super Admin', 'Administrador'];

    protected $listeners = ['CrearUsuario', 'EditarUsuario'];

    public function mount(): void
    {
        $this->rolesDisponibles = AccesoServicio::obtenerRoles();
    }

    public function CrearUsuario(): void
    {
        $this->mostrarFormulario = true;
        $this->resetFields();
    }

    public function EditarUsuario(int $id): void
    {
        $this->CrearUsuario();
        $this->userId = $id;

        try {
            $usuario = UsuarioServicio::obtenerUsuarioPorId($id);
            if ($usuario) {
                $this->name  = $usuario->name;
                $this->email = $usuario->email;
                // Tomar el primer rol (sólo hay uno)
                $this->rolSeleccionado = $usuario->roles?->first()?->name ?? '';
            }
        } catch (\Throwable $th) {
            $this->alert('error', 'Error al cargar usuario: ' . $th->getMessage());
        }
    }

    /**
     * Cuando el usuario elige un radio, limpia el input de nuevo rol.
     */
    public function seleccionarRol(string $nombre): void
    {
        $this->rolSeleccionado = $nombre;
        $this->nuevoRol = '';
        $this->resetErrorBag(['nuevoRol', 'rolSeleccionado']);
    }

    /**
     * Cuando el usuario escribe en el input de nuevo rol, deselecciona el radio activo.
     */
    public function escribirNuevoRol(): void
    {
        if (!empty($this->nuevoRol)) {
            $this->rolSeleccionado = '';
        }
        $this->resetErrorBag(['nuevoRol', 'rolSeleccionado']);
    }

    public function resetFields(): void
    {
        $this->userId        = false;
        $this->name          = null;
        $this->email         = null;
        $this->password      = null;
        $this->rolSeleccionado = '';
        $this->nuevoRol      = '';
        $this->resetErrorBag();
    }

    public function crear(): void
    {
        try {
            $data = [
                'name'  => $this->name,
                'email' => $this->email,
            ];

            if (!$this->userId || !empty($this->password)) {
                $data['password'] = $this->password;
            }

            // Determinar el rol final
            $rolFinal = $this->resolverRolFinal();

            // Validar datos del usuario
            $datosValidados = UsuarioServicio::validarDatos($data, $this->userId);

            // Guardar usuario con el rol resuelto (array de un elemento)
            UsuarioServicio::guardarUsuario(
                $this->userId,
                $datosValidados,
                $rolFinal ? [$rolFinal] : []
            );

            $this->dispatch('ActualizarUsuarios');
            $this->cerrarMostrarFormulario();
            $this->alert('success', 'Registros actualizados correctamente');

        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    /**
     * Resuelve y valida el rol final antes de guardar.
     * Si viene del input nuevo, lo crea si no existe.
     * Lanza excepción si el nombre es reservado o inválido.
     */
    protected function resolverRolFinal(): ?string
    {
        $nuevoRolTrimmed = trim($this->nuevoRol);

        // Tiene nuevo rol escrito
        if (!empty($nuevoRolTrimmed)) {

            // Verificar nombre reservado (case-insensitive)
            foreach ($this->rolesReservados as $reservado) {
                if (mb_strtolower($nuevoRolTrimmed) === mb_strtolower($reservado)) {
                    $this->addError('nuevoRol', "El nombre \"$reservado\" es un rol reservado y no puede usarse.");
                    throw ValidationException::withMessages([
                        'nuevoRol' => ["El nombre \"$reservado\" es un rol reservado y no puede usarse."],
                    ]);
                }
            }

            // Crear el rol si no existe
            AccesoServicio::crearRolSiNoExiste($nuevoRolTrimmed);

            // Recargar roles disponibles para que aparezca en futuros renders
            $this->rolesDisponibles = AccesoServicio::obtenerRoles();

            return $nuevoRolTrimmed;
        }

        // Tiene rol seleccionado por radio
        if (!empty($this->rolSeleccionado)) {
            return $this->rolSeleccionado;
        }

        // Sin rol — permitir guardar sin rol (según tu negocio puedes lanzar error aquí)
        return null;
    }

    public function cerrarMostrarFormulario(): void
    {
        $this->resetFields();
        $this->mostrarFormulario = false;
    }

    public function render()
    {
        return view('livewire.gestion-usuario.usuario-form-component');
    }
}