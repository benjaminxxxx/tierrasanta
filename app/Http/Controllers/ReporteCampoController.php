<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteCampoController extends Controller
{
    public function poblacion_plantas(){
        return view('reporte_campo.poblacion_plantas');
    }
}
