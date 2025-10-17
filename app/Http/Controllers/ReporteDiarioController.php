<?php

namespace App\Http\Controllers;

class ReporteDiarioController extends Controller
{
    public function index()
    {
        return view('livewire.gestion-planilla.administrar-registro-diario.indice-reporte-diario-planilla');
    }
    public function actividades_diarias()
    {
        return view('reporte.actividades_diarias');
    }
    public function riego(){
        return view('livewire.gestion-riego.reporte_diario_riego');
    }
    
}
