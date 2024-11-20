<?php

namespace App\Livewire;

use App\Models\Producto;
use Livewire\Component;

class ProductosStockComponent extends Component
{
    public $mostrarVista = false;
    public $productos = [];
    protected $listeners = ['verStock','actualizarAlmacen'=>'$refresh'];
    public function verStock(){
        $this->mostrarVista = true;
    }
    public function render()
    {
        $this->productos = Producto::with('compras')->get();
        return view('livewire.productos-stock-component');
    }
}
