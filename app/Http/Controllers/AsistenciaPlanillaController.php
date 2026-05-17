<?php

namespace App\Http\Controllers;

class AsistenciaPlanillaController extends Controller
{
    public function index($anio=null,$mes=null)
    {
        $data = [
            'anio'=>$anio,
            'mes'=>$mes,
        ];
        
        return view('livewire.gestion-planilla.administrar-planillero.indice-asistencias-empleados',$data);
    }
    public function blanco()
    {
        return view('planilla.blanco');
    }
    
}
