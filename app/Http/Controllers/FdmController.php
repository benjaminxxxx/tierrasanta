<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FdmController extends Controller
{
    public function costos(){
        return view('fdm.costos');
    }
}
