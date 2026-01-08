<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NutrienteController extends Controller
{
    public function index(){
        return view('livewire.gestion-nutriente.indice');
    }
    public function tabla_concentracion(){
        return view('livewire.gestion-nutriente.tabla-concentracion-indice');
    }
}
