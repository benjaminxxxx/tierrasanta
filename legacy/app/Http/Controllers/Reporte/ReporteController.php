<?php

namespace App\Http\Controllers\Reporte;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReporteController extends Controller
{
    public function reporte_diario(){
        return view('livewire.reporte.reporte-diario');
    }
    public function ResumenPlanilla(){
        return view('livewire.gestion-planilla.resumen-planilla-indice');
    }
    public function reporte_mensual(){
        return view('livewire.reporte.reporte-mensual');
    }
    public function reporte_anual(){
        return view('livewire.reporte.reporte-anual'); 
    }
    public function auditoria()
    {
        return view('livewire.reporte.auditoria');
    }
}
