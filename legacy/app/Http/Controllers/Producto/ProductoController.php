<?php

namespace App\Http\Controllers\Producto;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductoController extends Controller
{
    public function index(){
        return view('productos.index');
    }
    public function usos(){
        return view('livewire.gestion-insumos.usos');
    }
}
