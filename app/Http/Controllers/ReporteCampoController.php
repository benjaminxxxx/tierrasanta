<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteCampoController extends Controller
{
    public function poblacion_plantas(){
        return view('reporte_campo.poblacion_plantas');
    }
    public function evaluacion_brotes(){
        return view('reporte_campo.evaluacion_brotes');
    }
    public function evaluacion_infestacion_cosecha(){
        return view('reporte_campo.evaluacion_infestacion_cosecha');
    }
    public function evaluacion_proyeccion_rendimiento_poda(){
        return view('reporte_campo.evaluacion_proyeccion_rendimiento_poda');
    }
}
