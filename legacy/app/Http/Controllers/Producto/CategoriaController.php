<?php

namespace App\Http\Controllers\Producto;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoriaController extends Controller
{
    public function categorias()
    {
        return view('livewire.producto.categorias');
    }
    public function subcategorias()
    {
        return view('livewire.producto.subcategorias');
    }

}
