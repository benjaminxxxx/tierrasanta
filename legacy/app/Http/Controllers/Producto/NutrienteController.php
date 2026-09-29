<?php

namespace App\Http\Controllers\Producto;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NutrienteController extends Controller
{
    public function index(){
        return view('livewire.producto.nutriente-indice');
    }
    public function tabla_concentracion(){
        return view('livewire.producto.tabla-concentracion-indice');
    }
}
