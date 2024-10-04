<?php

namespace App\Http\Controllers;

use App\Models\Campo;
use App\Models\Empleado;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioCampos;
use App\Models\ReporteDiarioCuadrilla;
use App\Models\ReporteDiarioCuadrillaDetalle;
use App\Models\ReporteDiarioDetalle;
use Illuminate\Http\Request;

class ReporteDiarioController extends Controller
{
    public function index()
    {
        return view('reporte.reporte_diario');
    }
    public function riego(){
        return view('reporte.reporte_diario_riego');
    }
    
}
