<?php

namespace App\Http\Controllers\Sistema;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UsuarioController extends Controller
{
    public function index(){
        return view('livewire.sistema.usuarios-indice');
    }
    public function roles_permisos(){
        return view('sistema.roles_permisos');
    }
}
