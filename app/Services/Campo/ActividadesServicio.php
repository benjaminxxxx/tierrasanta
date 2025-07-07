<?php

namespace App\Services\Campo;

use App\Models\Actividad;
use App\Models\Labores;

class ActividadesServicio
{
    public static function obtenerLabores(){
        return Labores::all();
    }
    
    public static function obtenerEstandarProduccion($codigoLabor)
    {
        $labor = Labores::where('codigo', $codigoLabor)->first();
        if ($labor) {
            return [
                'estandar_produccion' => $labor->estandar_produccion,
                'unidades' => $labor->unidades,
                'tramos_bonificacion' => json_decode($labor->tramos_bonificacion, true) ?? [['hasta' => '', 'monto' => '']],
            ];
        }
        return [
            'estandar_produccion' => null,
            'unidades' => 'kg',
            'tramos_bonificacion' => [['hasta' => '', 'monto' => '']],
        ];
    }
}