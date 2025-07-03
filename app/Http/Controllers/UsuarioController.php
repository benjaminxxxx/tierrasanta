<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(){
        return view('sistema.usuarios');
    }
    public function roles_permisos(){
        return view('sistema.roles_permisos');
    }
}
