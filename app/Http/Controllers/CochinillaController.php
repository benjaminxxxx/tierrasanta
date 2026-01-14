<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CochinillaController extends Controller
{
    public function ingreso(){
        return view("cochinilla.ingreso");
    }
    public function venteado(){
        return view("livewire.gestion-cochinilla.cochinilla-venteado-indice");
    }
    public function filtrado(){
        return view("livewire.gestion-cochinilla.cochinilla-filtrado-indice");
    }
    public function cosecha_mamas(){
        return view("cochinilla.cosecha_mamas");
    }
    public function infestacion(){
        return view("cochinilla.infestacion");
    }
    public function ventas(){
        return view("cochinilla.ventas");
    }
}
