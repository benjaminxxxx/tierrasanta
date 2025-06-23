<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CochinillaController extends Controller
{
    public function ingreso(){
        return view("cochinilla.ingreso");
    }
    public function venteado(){
        return view("cochinilla.venteado");
    }
    public function filtrado(){
        return view("cochinilla.filtrado");
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
