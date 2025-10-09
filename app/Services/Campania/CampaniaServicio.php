<?php

namespace App\Services\Campania;

use App\Models\CampoCampania;
use App\Models\CuadRegistroDiario;

class CampaniaServicio
{
    public static function obtenerCostosManoObra($campaniaId){
        $campania = CampoCampania::findOrFail($campaniaId);
        $campo = $campania->campo;
        $fechaInicio = $campania->fecha_inicio;
        $fechaFin = $campania->fecha_fin??now();
        //Cuadrilla
        $registrosDiarios = CuadRegistroDiario::with(['detalleHoras'=>function ($detalleHoras) use ($campo){
            return $detalleHoras->where('campo_nombre',$campo);
        }])
        ->whereHas('detalleHoras',function ($detalleHoras) use ($campo){
            return $detalleHoras->where('campo_nombre',$campo);
        })
        ->whereBetween('fecha',[$fechaInicio,$fechaFin])
        ->get();
        
        $detalle = [];
        foreach ($registrosDiarios as $registroDiario) {
            $detalles = $registroDiario->detalleHoras;
           
            dd($detalles);
        }
        dd($registrosDiarios);

        return $registrosDiarios;
    }
}
