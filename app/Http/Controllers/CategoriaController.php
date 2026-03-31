<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoriaController extends Controller
{

    public function subcategorias()
    {
        return view('livewire.gestion-categorias.subcategorias');
    }

}
