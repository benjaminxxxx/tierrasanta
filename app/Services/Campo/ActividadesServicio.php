<?php

namespace App\Services\Campo;

use App\Models\Actividad;
use App\Models\Labores;

class ActividadesServicio
{
    public static function obtenerLabores()
    {
        return Labores::all();
    }

    public static function obtenerEstandarProduccion($actividadId)
    {
        $actividad = Actividad::find($actividadId);
        if ($actividad && $actividad->tramos_bonificacion != null) {
            return [
                'estandar_produccion' => $actividad->estandar_produccion,
                'unidades' => $actividad->unidades,
                'tramos_bonificacion' => json_decode($actividad->tramos_bonificacion, true) ?? [['hasta' => '', 'monto' => '']]
            ];
        }
        $labor = Labores::where('codigo', $actividad->codigo_labor)->first();
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