<?php

namespace App\Livewire;

use App\Models\Producto;
use Livewire\Component;

class ProductosStockComponent extends Component
{
    public $mostrarVista = false;
    public $productos = [];
    protected $listeners = ['verStock','actualizarAlmacen'=>'verStock'];
    public function verStock(){
        $this->productos = Producto::with('compras')->get();
        $this->mostrarVista = true;
    }
    public function render()
    {
        return view('livewire.productos-stock-component');
    }
}
