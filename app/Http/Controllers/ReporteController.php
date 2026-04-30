<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function reporte_diario(){
        return view('livewire.gestion-reportes.reporte-diario');
    }
    public function ResumenPlanilla(){
        return view('livewire.gestion-planilla.resumen-planilla-indice');
    }
    public function reporte_mensual(){
        return view('livewire.gestion-reportes.reporte-mensual');
    }
    public function reporte_anual(){
        return view('livewire.gestion-reportes.reporte-anual'); 
    }
    public function auditoria()
    {
        return view('livewire.gestion-reportes.auditoria');
    }
}
