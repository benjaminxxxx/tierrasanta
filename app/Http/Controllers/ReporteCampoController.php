<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteCampoController extends Controller
{
    public function poblacion_plantas(){
        return view('livewire.evaluaciones.evaluacion-poblacion-plantas');
    }
    public function evaluacion_brotes(){
        return view('livewire.evaluaciones.evaluacion_brotes');
    }
    public function evaluacion_infestacion_cosecha(){
        return view('reporte_campo.evaluacion_infestacion_cosecha');
    }
    public function evaluacion_proyeccion_rendimiento_poda(){
        return view('reporte_campo.evaluacion_proyeccion_rendimiento_poda');
    }
}
