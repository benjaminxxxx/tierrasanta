<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function general(){
        return view('gasto.general');
    }
    public function costos(){
        return view('gasto.costos_fijos_y_operativos');
    }
}
