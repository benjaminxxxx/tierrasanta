<?php

namespace App\Http\Controllers\Almacen;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AlmacenController extends Controller
{
    public function compraProductos($producto_id=null){
        return view('livewire.gestion-almacen.compra-productos',[
            'producto_id' => $producto_id
        ]);
    }
    public function distribucionCombustible(){
        return view('livewire.almacen.distribucion-combustible');
    }
    public function salidaProductos(){
        return view('almacen.salida_productos');
    }
    public function salidaCombustible(){
        return view('almacen.salida_combustible');
    }
}
