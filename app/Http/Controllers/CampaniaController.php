<?php

namespace App\Http\Controllers;

use App\Models\CampoCampania;
use Illuminate\Http\Request;

class CampaniaController extends Controller
{
    public function campanias(){
        return view("livewire.gestion-campania.indice-campanias");
    }
    public function costos($campaniaId = null){
        //Verificar si la campaña existe
        if($campaniaId && !CampoCampania::find($campaniaId)){
            abort(404);
        }
        return view('livewire.gestion-campania.indice-costos', compact('campaniaId'));
    }
}
