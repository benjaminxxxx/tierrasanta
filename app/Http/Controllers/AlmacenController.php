<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    public function salidaProductos(){
        return view('almacen.salida_productos');
    }
}