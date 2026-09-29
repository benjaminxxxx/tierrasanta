<?php

namespace App\Http\Controllers\Costos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FdmController extends Controller
{
    public function costos_generales(){
        return view('fdm.costos_generales');
    }
}
