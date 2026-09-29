<?php

namespace App\Http\Controllers\Costos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GastoController extends Controller
{
    public function general(){
        return view('gasto.general');
    }
    public function costo_mensual(){
        return view('livewire.costos.costo-mensual');
    }
    public function costos_mensuales(){
        return view('livewire.costos.costos-mensuales');
    }
    public function costos_generales(){
        return view('gasto.costos_generales');
    }
}
