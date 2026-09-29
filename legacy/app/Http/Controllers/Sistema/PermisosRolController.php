<?php

namespace App\Http\Controllers\Sistema;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PermisosRolController extends Controller
{
    public function index($rol){
        return view('livewire.sistema.permisos-rol', compact('rol'));
    }
}
