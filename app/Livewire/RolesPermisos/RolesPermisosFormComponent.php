<?php

namespace App\Livewire\RolesPermisos;
use App\Services\Sistema\AccesoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class RolesPermisosFormComponent extends Component
{
    use LivewireAlert;

    public $roles;
    public $permisos;
    public $mostrarModalCrearPermiso = false;
    public $mostrarModalCrearRol = false;
    public $nombrePermiso = '';
    public $rolesSeleccionados = [];
    public $nombreRol = '';
    public $permisosSeleccionados = [];
    public $modoEditarRol = false;
    public $modoEditarPermiso = false;
    public $rolIdEditando = null;
    public $permisoIdEditando = null;

    public function mount()
    {
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $this->roles = AccesoServicio::obtenerRoles();
        $this->permisos = AccesoServicio::obtenerPermisos();
    }
    public function editarRol($id)
    {
        try {
            $rol = AccesoServicio::obtenerRol($id);
            $this->rolIdEditando = $rol->id;
            $this->nombreRol = $rol->name;
            $this->permisosSeleccionados = $rol->permissions->pluck('id')->toArray();
            $this->modoEditarRol = true;
            $this->mostrarModalCrearRol = true;
        } catch (\Throwable $th) {
            $this->alert('error', 'Error al cargar el rol para edición: ' . $th->getMessage());
        }
    }

    public function editarPermiso($id)
    {
        try {
            $permiso = AccesoServicio::obtenerPermiso($id);
            $this->permisoIdEditando = $permiso->id;
            $this->nombrePermiso = $permiso->name;
            $this->rolesSeleccionados = $permiso->roles->pluck('id')->toArray();
            $this->modoEditarPermiso = true;
            $this->mostrarModalCrearPermiso = true;
        } catch (\Throwable $th) {
            $this->alert('error', 'Error al cargar el permiso para edición: ' . $th->getMessage());
        }
    }

    public function guardarRol()
    {
        $this->validate([
            'nombreRol' => 'required|min:3',
        ]);

        try {
            if ($this->modoEditarRol && $this->rolIdEditando) {
                AccesoServicio::actualizarRol(
                    $this->rolIdEditando,
                    $this->nombreRol,
                    $this->permisosSeleccionados
                );
                $mensaje = 'Rol actualizado correctamente.';
            } else {
                AccesoServicio::crearRol(
                    $this->nombreRol,
                    $this->permisosSeleccionados
                );
                $mensaje = 'Rol creado con permisos asignados.';
            }

            $this->reset(['nombreRol', 'permisosSeleccionados', 'mostrarModalCrearRol', 'modoEditarRol', 'rolIdEditando']);
            $this->cargarDatos();
            $this->alert('success', $mensaje);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function guardarPermiso()
    {
        $this->validate([
            'nombrePermiso' => 'required|min:3',
        ]);

        try {
            if ($this->modoEditarPermiso && $this->permisoIdEditando) {
                AccesoServicio::actualizarPermiso(
                    $this->permisoIdEditando,
                    $this->nombrePermiso,
                    $this->rolesSeleccionados
                );
                $mensaje = 'Permiso actualizado correctamente.';
            } else {
                AccesoServicio::crearPermiso(
                    $this->nombrePermiso,
                    $this->rolesSeleccionados
                );
                $mensaje = 'Permiso creado y asignado correctamente.';
            }

            $this->reset(['nombrePermiso', 'rolesSeleccionados', 'mostrarModalCrearPermiso', 'modoEditarPermiso', 'permisoIdEditando']);
            $this->cargarDatos();
            $this->alert('success', $mensaje);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function eliminarPermiso($permisoId)
    {
        try {
            AccesoServicio::eliminarPermiso($permisoId);
            $this->cargarDatos();
            $this->alert('success', 'Permiso eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function eliminarRol($rolId)
    {
        try {
            AccesoServicio::eliminarRol($rolId);
            $this->cargarDatos();
            $this->alert('success', 'Rol eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.roles-permisos.roles-permisos-form-component');
    }
}
