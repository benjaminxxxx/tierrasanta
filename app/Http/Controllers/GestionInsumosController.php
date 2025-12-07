<?php

namespace App\Http\Controllers;

use App\Models\InsKardex;
use App\Models\InsKardexReporte;
use Illuminate\Http\Request;

class GestionInsumosController extends Controller
{
    public function kardex()
    {
        return view('livewire.gestion-insumos.kardex-index');
    }
    public function kardexDetalle($insumoKardexId){
        $reporte = InsKardex::find($insumoKardexId);
        if (!$reporte) {
            return redirect()->route('gestion_insumos.kardex')
                ->with('error', 'El kardex no existe');
        }
        return view('livewire.gestion-insumos.kardex-detalle-index', ['insumoKardexId' => $insumoKardexId]);
    }
    public function kardexReportes()
    {
        return view('livewire.gestion-insumos.kardex-reportes-index');
    }
    public function kardexReporte($insumoKardexReporteId)
    {
        $reporte = InsKardexReporte::find($insumoKardexReporteId);
        if (!$reporte) {
            return redirect()->route('gestion_insumos.kardex.reportes')
                ->with('error', 'El reporte de kardex no existe');
        }
        return view('livewire.gestion-insumos.kardex-reporte-index', ['insumoKardexReporteId' => $insumoKardexReporteId]);
    }
}
