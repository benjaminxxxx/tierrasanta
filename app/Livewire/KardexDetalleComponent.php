<?php

namespace App\Livewire;

use App\Exports\KardexAlmacenExport;
use App\Exports\KardexProductoExport;
use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
use App\Models\Empresa;
use App\Models\Kardex;
use App\Models\Producto;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use App\Models\KardexProducto;
use Livewire\WithFileUploads;


class KardexDetalleComponent extends Component
{
    use LivewireAlert;
    
    public $kardexId;
    public $kardex;
    public $search;
    public $resultado;
    public $producto;
    public $productoSeleccionadoId;
    protected $listeners = ['kardexProductoRegistrado' => 'listarKardex', 'importacionRealizada' => 'listarKardex', 'eliminacionConfirmar'];
    public function mount()
    {
        $this->kardex = Kardex::find($this->kardexId);
        $productoCargado = Session::get('producto_seleccionado_id');
        if($productoCargado){
            $this->seleccionarProducto($productoCargado);
        }
    }
   
    public function quitarProducto(){
        $this->producto = null;
        $this->search = null;
        $this->productoSeleccionadoId = null;
        Session::forget('producto_seleccionado_id');
    }
    public function seleccionarProducto($productoId){
        $producto = Producto::find($productoId);
        if($producto){
            $this->resultado = null;
            $this->producto = $producto;
            $this->productoSeleccionadoId = $this->producto->id;
            Session::put('producto_seleccionado_id', $this->productoSeleccionadoId);
        }
    }
    public function updatedSearch(){
        $this->resultado = Producto::where(function($query) {
            // Filtrar por nombre_comercial
            $query->where('nombre_comercial', 'like', '%' . $this->search . '%')
                  // Filtrar también por ingrediente_activo
                  ->orWhere('ingrediente_activo', 'like', '%' . $this->search . '%');
        })
        ->take(10)
        ->get();
    }

    
    public function render()
    {
        return view('livewire.kardex-detalle-component');
    }
}
