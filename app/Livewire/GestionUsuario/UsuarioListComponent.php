<?php

namespace App\Livewire\GestionUsuario;

use App\Models\User;
use App\Services\Sistema\UsuarioServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class UsuarioListComponent extends Component
{
    use LivewireAlert;
    public $usuarios;
    protected $listeners = ['confirmarEliminar', 'ActualizarUsuarios' => '$refresh'];

    public function updateStatus($id, $status)
    {
        $usuario = User::find($id);
        if ($usuario) {
            $usuario->estado = $status;
            $usuario->save();
        }
    }
    public function confirmarEliminacion($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el usuario?', [
            'onConfirmed' => 'confirmarEliminar',
            'data' => ['id' => $id],
        ]);
    }
    public function confirmarEliminar($data)
    {
        try {
            $id = isset($data['id']) ? $data['id'] : null;

            if (!$id) {
                return;
            }
            UsuarioServicio::eliminarUsuarioPorId($id);
            $this->alert('success', "El usuario se eliminó correctamente");
            $this->dispatch("ActualizarUsuarios");
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        $this->usuarios = UsuarioServicio::obtenerUsuariosConRoles();
        return view('livewire.gestion-usuario.usuario-list-component');
    }
}
