<?php

namespace App\Services\Produccion\Planificacion;

use App\Models\CampoCampania as Campania;
use App\Services\Reportes\RptProduccionPlanificacionCampania;
use Maatwebsite\Excel\Facades\Excel;

class CampaniaServicio
{
    public function buscarCampaniasPorCampo(string $campo)
    {
        return Campania::where('campo', $campo)
            ->orderBy('nombre_campania', 'desc')
            ->get();
    }
    /**
     * Elimina una campaña con validaciones previas.
     *
     * @param int $campaniaId
     * @throws \Exception
     */
    public function eliminarCampania($campaniaId)
    {
        // 1. Buscar campaña
        $campania = Campania::find($campaniaId);

        if (!$campania) {
            throw new \Exception("La campaña no existe o ya fue eliminada.");
        }

        // 2. Validación: no debe tener evaluacionPoblacionPlantas
        if ($campania->evaluacionPoblacionPlantas()->exists()) {
            throw new \Exception("No se puede eliminar la campaña porque tiene evaluaciones de población de plantas registradas.");
        }

        // 3. Eliminar campaña
        try {
            $campania->delete();
        } catch (\Throwable $th) {
            throw new \Exception("Error al eliminar la campaña: " . $th->getMessage());
        }

        return true;
    }
    public function descargarReporteCampania($registros, $campo, $campania)
    {

        return app(RptProduccionPlanificacionCampania::class)->descargarReporteGeneral($registros, $campo, $campania);
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

    /**
     * Actualiza uno o varios campos de la campaña.
     * 
     * @param int   $campaniaId    ID de campaña
     * @param array $valores       ['campo' => valor, ...]
     */
    public function actualizarMetricas(int $campaniaId, array $valores): void
    {
        $campania = Campania::findOrFail($campaniaId);

        // Solo actualiza los campos enviados
        $campania->update($valores);
    }

}