<?php

namespace App\Http\Controllers\Reporte;

use App\Http\Controllers\Controller;

class ReporteDiarioController extends Controller
{
    public function index()
    {
        return view('livewire.planilla.registro-diario.indice-reporte-diario-planilla');
    }
    public function actividades_diarias()
    {
        return view('reporte.actividades_diarias');
    }
    
}
