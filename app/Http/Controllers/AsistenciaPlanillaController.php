<?php

namespace App\Http\Controllers;

use App\Models\Dia;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsistenciaPlanillaController extends Controller
{
    public function index($anio=null,$mes=null)
    {
        $data = [
            'anio'=>$anio,
            'mes'=>$mes,
        ];
        return view('planilla.horas',$data);
    }
    public function blanco()
    {
        return view('planilla.blanco');
    }
    
}
