<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function categorias()
    {
        return view('livewire.gestion-categorias.categorias');
    }
    public function subcategorias()
    {
        return view('livewire.gestion-categorias.subcategorias');
    }

}
