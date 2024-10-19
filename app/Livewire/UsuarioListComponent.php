<?php

namespace App\Livewire;

use App\Models\User;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class UsuarioListComponent extends Component
{
    use LivewireAlert;
    public $usuarios;
    protected $listeners = ['confirmarEliminar','ActualizarUsuarios'=>'$refresh'];

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
        $this->alert('question', '¿Está seguro(a) que desea eliminar el usuario?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'id' => $id,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        try {
            $id = isset($data['id'])?$data['id']:null;
            if(!$id){
                return;
            }
            $usuario = User::where('id', $id);
            if ($usuario) {
                $usuario->delete();
                $this->alert('success',"El usuario se eliminó correctamente");
            }
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function render()
    {
        $this->usuarios = User::all();
        return view('livewire.usuario-list-component');
    }
}
