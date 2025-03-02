<?php

namespace App\Livewire;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $productoIdEliminar;
    public $search;
    public $categoria_id_filtro;
    public $sortField = 'nombre_comercial'; 
    public $sortDirection = 'asc';
    public $categorias;

    protected $listeners = ['ActualizarProductos' => '$refresh', 'eliminacionConfirmada'];
    public function mount(){
        $this->categorias = CategoriaProducto::all();
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function render()
    {
        $productos = Producto::where(function($query) {
            // Filtrar por nombre_comercial
            $query->where('nombre_comercial', 'like', '%' . $this->search . '%')
                  // Filtrar también por ingrediente_activo
                  ->orWhere('ingrediente_activo', 'like', '%' . $this->search . '%');
        })
        
        ->when($this->categoria_id_filtro, function ($query) {
            return $query->where('categoria', $this->categoria_id_filtro);
        })
        // Ordenar por el campo especificado y dirección
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate(20);

        return view('livewire.productos-component', [
            'productos' => $productos
        ]);
    }
    public function confirmarEliminacion($id)
    {
        $this->productoIdEliminar = $id;

        $this->alert('question', '¿Está seguro que desea eliminar al Producto?', [
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
        if ($this->productoIdEliminar) {
            $producto = Producto::find($this->productoIdEliminar);
            if ($producto) {
                $producto->delete();
                $this->productoIdEliminar = null;
                $this->alert('success', 'Producto Eliminado');
            }
        }
    }
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}
