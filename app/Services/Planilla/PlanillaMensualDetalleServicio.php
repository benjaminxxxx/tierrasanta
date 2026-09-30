<?php

namespace App\Services\Planilla;

use App\Models\PlanMensualDetalle;
use App\Models\PlanMensualPersonal;


class PlanillaMensualDetalleServicio
{
    public static function obtenerOrden($mes, $anio)
    {
        return PlanMensualDetalle::whereHas(
            'planillaMensual',
            fn($q) =>
            $q->where('mes', $mes)->where('anio', $anio)
        )
            ->pluck('orden', 'plan_empleado_id')
            ->toArray();
    }
    /**
     * Guarda o actualiza un registro de PlanMensualDetalle.
     *
     * @param  array  $data  Datos completos que llegan desde Handsontable.
     * @return PlanMensualDetalle
     */
    public static function guardar(array $data, $id = null): PlanMensualDetalle
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
    /**
     * Costo del mes por empleado, desde el PLAME (plan_mensual_personals):
     * [plan_empleado_id => ['sueldo_blanco_pagado', 'sueldo_negro_pagado', 'total_horas']]
     *
     * - blanco: costo formal del PLAME = ingresos (0117…0904) + aportes del empleador.
     * - negro: lo que la empresa pagó por encima de eso (costo real total − blanco).
     * Antes leía columnas de plan_mensual_detalles que ya no se llenan (solo hasta 01/2026).
     */
    public static function obtenerRegistrosMensualesPorCampo($mes, $anio)
    {
        return PlanMensualPersonal::with('planMensual')
            ->whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->get()
            ->mapWithKeys(function (PlanMensualPersonal $p) {
                $blanco = (float) $p->plame_remuneracion_bruta
                    + (float) $p->plame_0312_bonif_ext_temp
                    + (float) $p->plame_0314_beta_30
                    + (float) $p->plame_0406_gratif_fiestas_navidad
                    + (float) $p->plame_0904_cts
                    + (float) $p->aportes_empleador;
                $total = (float) ($p->pagado_sueldo_bruto_negro ?? 0);

                return [$p->plan_empleado_id => [
                    'plan_empleado_id' => $p->plan_empleado_id,
                    'sueldo_blanco_pagado' => round($blanco, 2),
                    'sueldo_negro_pagado' => round(max(0, $total - $blanco), 2),
                    'total_horas' => (float) $p->plame_total_horas,
                ]];
            })
            ->toArray();
    }
}