<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermisosRolController extends Controller
{
    public function index($rol){
        return view('livewire.gestion-usuario.permisos-rol', compact('rol'));
    }
}
