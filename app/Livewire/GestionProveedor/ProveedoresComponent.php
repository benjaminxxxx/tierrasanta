<?php

namespace App\Livewire\GestionProveedor;

use App\Models\TiendaComercial;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProveedoresComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $search;
    protected $listeners = ['ActualizarProveedores'=>'$refresh', 'eliminacionConfirmada'];
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function confirmarEliminacion($id)
    {
        $this->confirm('¿Está seguro que desea eliminar el registro?', [
            'onConfirmed' => 'eliminacionConfirmada',
            'data' => ['id' => $id],
        ]);
    }
    public function eliminacionConfirmada($data)
    {
        try {
            $proveedor = TiendaComercial::findOrFail($data['id']);
            $proveedor->delete();
            $this->alert('success','Proveedor Eliminado');
        } catch (\Exception $e) {
            $this->alert('error','No se puede eliminar el proveedor porque está asociado a otros registros.');
        }
    }
    public function render()
    {
        $proveedores = TiendaComercial::where('nombre', 'like', '%' . $this->search . '%')->orderBy('nombre')->paginate(20);
        return view('livewire.gestion-proveedor.proveedores-component',[
            'proveedores'=>$proveedores
        ]);
    }
}
