<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CampaniaController extends Controller
{
    public function campanias(){
        return view("campanias.lista-campanias");
    }
}
