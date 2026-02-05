<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanMensualDetalle;


class PlanillaMensualDetalleServicio
{
    public static function obtenerRegistrosMensualesPorCampo($mes, $anio)
    {
        return PlanMensualDetalle::select('plan_empleado_id', 'sueldo_negro_pagado', 'sueldo_blanco_pagado','total_horas')
            ->whereHas('planillaMensual', function ($query) use ($mes, $anio) {
                $query->where('mes', $mes)->where('anio', $anio);
            })
            ->with(['planillaMensual'])
            ->get()
            ->keyBy('plan_empleado_id')
            ->toArray();
        /*
        array:43 [▼ // app\Services\FDM\PlanillaFdmServicio.php:19
            2179 => array:4 [▶]
            2176 => array:4 [▶]
             2173 => array:5 [▼
                "plan_empleado_id" => 2173
                "sueldo_negro_pagado" => "346.30"
                "sueldo_blanco_pagado" => "2100.00"
                "total_horas" => "44.00"
                "planilla_mensual" => null
            ] */
    }
}