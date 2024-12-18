<?php

namespace App\Livewire;

use App\Models\Producto;
use Livewire\Component;

class ProductosStockComponent extends Component
{
    public $mostrarVista = false;
    public $productos = [];
    public $tipo;
    protected $listeners = ['verStock','actualizarAlmacen'=>'$refresh'];
    public function verStock($tipo='normal'){
        $this->tipo = $tipo;
        $this->mostrarVista = true;
    }
    public function render()
    {
        $this->productos = Producto::deTipo($this->tipo);
        return view('livewire.productos-stock-component');
    }
}
