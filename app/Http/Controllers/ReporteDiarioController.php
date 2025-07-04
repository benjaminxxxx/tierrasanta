<?php

namespace App\Http\Controllers;

class ReporteDiarioController extends Controller
{
    public function index()
    {
        return view('reporte.reporte_diario');
    }
    public function actividades_diarias()
    {
        return view('reporte.actividades_diarias');
    }
    public function riego(){
        return view('reporte.reporte_diario_riego');
    }
    
}
