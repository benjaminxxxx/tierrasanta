<?php

namespace App\Livewire;

use App\Models\CompraProducto;
use App\Models\Producto;
use App\Models\TiendaComercial;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosCompraComponent extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $productoId;
    public $compraId;
    public $mostrarFormulario = false;
    public $sortField = 'fecha_compra';
    public $sortDirection = 'desc';
    public $compraIdEliminar;
    public $producto;
    public $modo;
    public $filtroTipoKardex;

    protected $listeners = ['VerComprasProducto', 'eliminacionConfirmadaCompra', 'actualizarAlmacen' => '$refresh','actualizarCompraProductos'];
    public function actualizarCompraProductos($data)
    {
        $compras = $data['compras'] ?? 0;
        $almacen = $data['almacen'] ?? 0;
    
        $this->alert("success", "Registros Importados Correctamente, ({$compras}) compras y {$almacen} registros de salida.");
    }

    public function VerComprasProducto($id)
    {
        $this->productoId = $id;

        $this->producto = Producto::find($this->productoId);
        if (!$this->producto) {
            return $this->alert('error', 'El producto ya no existe.');
        }

        $this->mostrarFormulario = true;
        $this->sortField = 'fecha_compra';
        $this->sortDirection = 'desc';
    }
    public function mount()
    {

    }
    public function enable($id)
    {
        $compra = CompraProducto::find($id);
        if ($compra) {
            $compra->estado = '1';
            $compra->save();
        }
    }
    public function disable($id)
    {
        $compra = CompraProducto::find($id);
        if ($compra) {
            $compra->estado = '0';
            $compra->save();
        }
    }
    public function closeForm()
    {
        $this->mostrarFormulario = false;
    }
    public function continuar()
    {
        $this->dispatch("continuar", $this->productoId);
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
    public function confirmarEliminacion($id)
    {
        $this->compraIdEliminar = $id;

        $this->alert('question', '¿Está seguro que desea eliminar la compra?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionConfirmadaCompra',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function eliminacionConfirmadaCompra()
    {
        if ($this->compraIdEliminar) {
            $compra = CompraProducto::find($this->compraIdEliminar);
            if ($compra) {
                $compra->delete();
                $this->compraIdEliminar = null;
                $this->resetPage();
                $this->alert('success', 'Compra Eliminada');
            }
        }
    }
    public function render()
    {
        $compras = null;
        if ($this->productoId) {
            $query = CompraProducto::query();

            $query->where('producto_id', $this->productoId)
            ->orderBy($this->sortField, $this->sortDirection);

            if($this->filtroTipoKardex){
                $query->where('tipo_kardex',$this->filtroTipoKardex);
            }

            $compras = $query->paginate(10);
        }
        return view('livewire.productos-compra-component', [
            'compras' => $compras
        ]);
    }
}
