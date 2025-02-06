<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
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
    public $historiales = [];
    protected $listeners = ['verHistorialSalidaPorCompra'];
    public function verHistorialSalidaPorCompra($salidaId){
        $this->salidaId = $salidaId;
        $this->historiales = [];
        $salida = AlmacenProductoSalida::find($this->salidaId);
        if($salida){
            $compras = $salida->compraStock;
            foreach ($compras as $compra) {
                $historial = CompraSalidaStock::where('compra_producto_id',$compra->compra_producto_id)->get();
               
                $this->historiales[] = [
                    'entrada'=>CompraProducto::find($compra->compra_producto_id),
                    'historial'=>$historial,
                ];
            }
            
        }
        /*
        $this->entrada = CompraProducto::find($compraId);
        $this->historial = AlmacenProductoSalida::where('compra_producto_id',$compraId)->get();
        */
        $this->mostrarHistorial = true;
        //$this->dispatch('mostrarHistorial2');

    }
    public function render()
    {
        return view('livewire.almacen-salida-historial-por-compra-component');
    }
}
