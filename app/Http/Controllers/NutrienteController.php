<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NutrienteController extends Controller
{
    public function index(){
        return view('nutrientes.index');
    }
    public function tabla_concentracion(){
        return view('nutrientes.tabla_concentracion');
    }
}
