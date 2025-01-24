<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductividadController extends Controller
{
    public function avance(){
        return view('productividad.avance');
    }
}
