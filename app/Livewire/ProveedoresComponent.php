<?php

namespace App\Livewire;

use App\Models\TiendaComercial;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProveedoresComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $proveedorIdEliminar;
    public $search;
    protected $listeners = ['ActualizarProveedores'=>'$refresh', 'eliminacionConfirmada'];
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $proveedores = TiendaComercial::where('nombre', 'like', '%' . $this->search . '%')->orderBy('nombre')->paginate(20);
        return view('livewire.proveedores-component',[
            'proveedores'=>$proveedores
        ]);
    }
    public function confirmarEliminacion($id)
    {
        $this->proveedorIdEliminar = $id;

        $this->alert('question', '¿Está seguro que desea eliminar al Proveedor?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionConfirmada',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function eliminacionConfirmada()
    {
        if ($this->proveedorIdEliminar) {
            $proveedor = TiendaComercial::find($this->proveedorIdEliminar);
            if ($proveedor) {
                $proveedor->delete();
                $this->proveedorIdEliminar = null;
                $this->alert('success','Proveedor Eliminado');
            }
        }
    }
}
