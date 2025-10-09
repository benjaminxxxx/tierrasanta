<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function ResumenPlanilla(){
        return view('reporte.resumen_planilla');
    }
}
