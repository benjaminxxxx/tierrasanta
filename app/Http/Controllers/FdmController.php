<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FdmController extends Controller
{
    public function costos_generales(){
        return view('fdm.costos_generales');
    }
}
