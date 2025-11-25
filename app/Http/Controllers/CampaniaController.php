<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CampaniaController extends Controller
{
    public function campanias(){
        return view("livewire.gestion-campania.indice-campanias");
    }
    public function costos(){
        return view('livewire.gestion-campania.indice-costos');
    }
}
