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
            /**
             * "id" => 191
                "registro_diario_id" => 565
                "actividad_id" => null
                "campo_nombre" => "1"
                "hora_inicio" => "07:00:00"
                "hora_fin" => "10:00:00"
                "produccion" => null
                "costo_bono" => "0.00"
                "created_at" => "2025-08-08 19:23:21"
                "updated_at" => "2025-08-08 19:23:21"
                "codigo_labor" => 22
            ]
             */
            dd($detalles);
        }
        dd($registrosDiarios);

        return $registrosDiarios;
    }
}
