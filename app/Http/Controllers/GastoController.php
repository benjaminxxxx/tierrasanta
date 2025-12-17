<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function general(){
        return view('gasto.general');
    }
    public function costo_mensual(){
        return view('livewire.gestion-costos.costo-mensual');
    }
    public function costos_mensuales(){
        return view('livewire.gestion-costos.costos-mensuales');
    }
    public function costos_generales(){
        return view('gasto.costos_generales');
    }
}
