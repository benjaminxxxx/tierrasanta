<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function general(){
        return view('gasto.general');
    }
}
