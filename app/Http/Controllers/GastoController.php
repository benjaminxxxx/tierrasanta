<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function general(){
        return view('gasto.general');
    }
    public function costos_mensuales(){
        return view('gasto.costos_mensuales');
    }
    public function costos_generales(){
        return view('gasto.costos_generales');
    }
}
