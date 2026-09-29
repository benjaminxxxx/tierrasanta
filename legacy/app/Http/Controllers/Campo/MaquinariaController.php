<?php

namespace App\Http\Controllers\Campo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MaquinariaController extends Controller
{
    public function index(){
        return view('maquinarias.index');
    }
}
