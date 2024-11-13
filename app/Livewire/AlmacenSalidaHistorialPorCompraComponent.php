<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use Livewire\Component;

class AlmacenSalidaHistorialPorCompraComponent extends Component
{
    public $mostrarHistorial = false;
    public $historial = [];
    public $historiaCantidad = 0;
    public $historiaCostoPorUnidad = 0;
    public $historiaTotalCosto = 0;
    public $entrada;
    public $unidad = '';
    public $salidaId;
    protected $listeners = ['verHistorialSalidaPorCompra'];
    public function verHistorialSalidaPorCompra($compraId,$salidaId){
        $this->salidaId = $salidaId;
        $this->entrada = CompraProducto::find($compraId);
        $this->historial = AlmacenProductoSalida::where('compra_producto_id',$compraId)->get();
        $this->historiaCantidad = number_format($this->historial->sum('cantidad'),3);
        $this->historiaCostoPorUnidad = number_format($this->historial->sum('costo_por_kg'),2);
        $this->historiaTotalCosto = number_format($this->historial->sum('total_costo'),2);
        $this->mostrarHistorial = true;
        $this->dispatch('mostrarHistorial2');

    }
    public function render()
    {
        return view('livewire.almacen-salida-historial-por-compra-component');
    }
}
