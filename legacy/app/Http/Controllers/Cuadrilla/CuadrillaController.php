<?php

namespace App\Http\Controllers\Cuadrilla;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
    public function resumen_anual(){
        return view("livewire.gestion-cuadrilla.resumen_anual");
    }
    public function gestion()
    {
      
        return view("cuadrilla.gestion.indice");
    }
}
