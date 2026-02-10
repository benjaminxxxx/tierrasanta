<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanMensualDetalle;


class PlanillaMensualDetalleServicio
{
    /**
     * Guarda o actualiza un registro de PlanMensualDetalle.
     *
     * @param  array  $data  Datos completos que llegan desde Handsontable.
     * @return PlanMensualDetalle
     */
    public static function guardar(array $data,$id = null): PlanMensualDetalle
    {
        if ($id) {
            // UPDATE
            $detalle = PlanMensualDetalle::find($id);

            if (!$detalle) {
                // Edge case: Handsontable envió un ID que ya no existe
                // Forzar un create limpio
                return PlanMensualDetalle::create($data);
            }

            $detalle->update($data);
            return $detalle;
        }

        // CREATE
        return PlanMensualDetalle::create($data);
    }
    public static function obtenerRegistrosMensualesPorCampo($mes, $anio)
    {
        return PlanMensualDetalle::select('plan_empleado_id', 'sueldo_negro_pagado', 'sueldo_blanco_pagado', 'total_horas')
            ->whereHas('planillaMensual', function ($query) use ($mes, $anio) {
                $query->where('mes', $mes)->where('anio', $anio);
            })
            ->with(['planillaMensual'])
            ->get()
            ->keyBy('plan_empleado_id')
            ->toArray();
    }
}