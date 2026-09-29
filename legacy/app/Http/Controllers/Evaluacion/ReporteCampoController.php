<?php

namespace App\Http\Controllers\Evaluacion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReporteCampoController extends Controller
{
    public function poblacion_plantas(){
        return view('livewire.evaluacion.evaluacion-poblacion-plantas');
    }
    public function evaluacion_brotes($campaniaId=null){
        return view('livewire.evaluacion.evaluacion_brotes',[
            'campaniaId'=>$campaniaId
        ]);
    }
    public function evaluacion_infestacion_cosecha(){
        return view('reporte_campo.evaluacion_infestacion_cosecha');
    }
    public function evaluacion_proyeccion_rendimiento_poda(){
        return view('livewire.evaluacion.proyeccion-rendimiento-poda-indice');
    }
}
