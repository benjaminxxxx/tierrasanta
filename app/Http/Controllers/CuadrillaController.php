<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CuadrillaController extends Controller
{
    public function registro_diario(){
        return view("cuadrilla.gestion.reporte_diario");
    }
    public function reporte_semanal(){
        return view("livewire.gestion-cuadrilla.reporte_semanal");
    }
    public function pagos(){
        return view("cuadrilla.gestion.pagos");
    }
    public function bonificaciones(){
        return view("cuadrilla.gestion.bonificaciones");
    }
    public function resumen_anual(){
        return view("livewire.gestion-cuadrilla.resumen_anual");
    }
    public function gestion()
    {
      
        return view("cuadrilla.gestion.indice");
    }
}
