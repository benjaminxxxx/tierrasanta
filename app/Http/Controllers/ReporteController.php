<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function PagoCuadrilla(){
        return view('reporte.pago_cuadrilla');
    }
}
