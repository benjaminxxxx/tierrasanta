<?php

namespace App\Livewire;

use App\Models\Producto;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosStockComponent extends Component
{
    use WithPagination;
    use LivewireAlert;
    public $mostrarVista = false;
    public $tipo;
    public $search = '';
    protected $listeners = ['verStock','actualizarAlmacen'=>'$refresh'];
    public function verStock($tipo='normal'){
        $this->tipo = $tipo;
        $this->mostrarVista = true;
    }
    public function render()
    {
        $productos = Producto::deTipo($this->tipo)
        ->where('nombre_comercial','like', '%' . $this->search . '%')
        ->paginate(10);
        return view('livewire.productos-stock-component',[
            'productos'=>$productos
        ]);
    }
}
