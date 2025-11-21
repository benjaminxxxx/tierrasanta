<?php

namespace App\Services\Produccion\Planificacion;

use App\Models\CampoCampania as Campania;

class CampaniaServicio
{
    public function buscarCampaniasPorCampo(string $campo)
    {
        return Campania::where('campo', $campo)
            ->orderBy('nombre_campania', 'desc')
            ->get();
    }
    /**
     * Recalcula los promedios de población (Día 0 y Resiembra) basándose en el historial.
     */
    public function actualizarMetricasPoblacion(int $campaniaId): void
    {
        // Cargar campaña con su evaluación única
        $campania = Campania::with('evaluacionPoblacionPlantas')->findOrFail($campaniaId);
        $evaluacion = $campania->evaluacionPoblacionPlantas; // relación uno-uno

        // Valores por defecto
        $data = [
            'pp_dia_cero_fecha_evaluacion' => null,
            'pp_dia_cero_numero_pencas_madre' => null,
            'pp_resiembra_fecha_evaluacion' => null,
            'pp_resiembra_numero_pencas_madre' => null,
        ];

        if ($evaluacion) {

            // Día cero
            $data['pp_dia_cero_fecha_evaluacion'] = $evaluacion->fecha_eval_cero;
            $data['pp_dia_cero_numero_pencas_madre'] = $evaluacion->promedio_plantas_ha_cero;
            $data['pp_resiembra_fecha_evaluacion'] = $evaluacion->fecha_eval_resiembra;
            $data['pp_resiembra_numero_pencas_madre'] = $evaluacion->promedio_plantas_ha_resiembra;

        }

        // Guardar métricas en campaña
        $campania->update($data);
    }

}