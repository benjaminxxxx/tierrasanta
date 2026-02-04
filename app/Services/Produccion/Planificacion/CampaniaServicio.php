<?php

namespace App\Services\Produccion\Planificacion;

use App\Models\CampoCampania as Campania;
use App\Models\CochinillaInfestacion;
use App\Services\Campania\Data\DataCostoServicio;
use App\Services\Campania\Data\DataInsumoServicio;
use App\Services\Campania\Data\DataManoObraServicio;
use App\Services\Campania\Exports\ExportCampaniaServicio;
use App\Services\Reportes\RptProduccionPlanificacionCampania;
use Exception;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class CampaniaServicio
{
    public function generarBddMensual(int $campaniaId)
    {
        $campania = Campania::find($campaniaId);

        if (!$campania) {
            throw new Exception("La campaña no existe");
        }
        
        // 1. Recolección de datos de múltiples fuentes
        $informacionPlanilla = app(DataManoObraServicio::class)->generarPlanillerosPor(
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );
        $informacionCuadrilla = app(DataManoObraServicio::class)->generarCuaderillerosPor(
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        $informacionMaquinaria = app(DataInsumoServicio::class)->generarCostoMaquinariaPor(
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        $informacionFertilizante = app(DataInsumoServicio::class)->generarCostoFertilizantePor(
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        $informacionPesticida = app(DataInsumoServicio::class)->generarCostoPesticidaPor(
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        $informacionCosto = app(DataCostoServicio::class)->generarCostoPor(
            $campania->id,
        );

        $informacionConsumo = [];   // Aquí vendrían tus otros servicios

        // 2. Combinar todos los arrays
        $informacionCombinada = array_merge(
            $informacionPlanilla,
            $informacionCuadrilla,
            $informacionConsumo,
            $informacionMaquinaria,
            $informacionFertilizante,
            $informacionPesticida,
            $informacionCosto
        );

        // 3. Definir los valores comunes
        $tipoCambioValor = $campania->tipo_cambio; // Ejemplo: podrías traerlo de un servicio o del objeto campania
        $nombreCampaniaValor = $campania->nombre_campania;

        // 4. Inyectar campos adicionales a cada elemento
        $informacionCombinada = array_map(function ($item) use ($tipoCambioValor, $nombreCampaniaValor) {
            $item['tipo_cambio'] = $tipoCambioValor;
            $item['campania'] = $nombreCampaniaValor;
            return $item;
        }, $informacionCombinada);

        // 2. Ordenamiento
        usort($informacionCombinada, function ($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });

        // 3. Delegar generación de Excel al Servicio
        // Pasamos un objeto simple con los datos de configuración necesarios
        $config = (object) [
            'campo' => $campania->campo,
            'nombre_campania' => $campania->nombre_campania
        ];

        $filePath = app(ExportCampaniaServicio::class)->generarExcelMensual($config, $informacionCombinada);

        // 4. Actualizar el modelo con la ruta retornada
        $campania->update([
            'gasto_resumen_bdd_file' => $filePath
        ]);
    }
    public function registrarHistorialDeInfestaciones(int $campaniaId, string $tipo = 'infestacion'): void
    {
        // Cargar campaña
        $campania = Campania::findOrFail($campaniaId);

        $fechaInicio = Carbon::parse($campania->fecha_inicio);
        $fechaFin = $campania->fecha_fin ? Carbon::parse($campania->fecha_fin) : null;
        $campo = $campania->campo;

        // 1. Desvincular infestaciones anteriores
        CochinillaInfestacion::where('campo_campania_id', $campaniaId)
            ->where('tipo_infestacion', $tipo)
            ->update(['campo_campania_id' => null]);

        // 2. Reasignar infestaciones dentro del rango
        CochinillaInfestacion::where('tipo_infestacion', $tipo)
            ->where('campo_nombre', $campo)
            ->where('fecha', '>=', $fechaInicio)
            ->when($fechaFin, fn($q) => $q->where('fecha', '<=', $fechaFin))
            ->update(['campo_campania_id' => $campaniaId]);

        // 3. Reconsultar infestaciones ya vinculadas
        $infestaciones = CochinillaInfestacion::where('campo_campania_id', $campaniaId)
            ->where('tipo_infestacion', $tipo)
            ->orderBy('fecha')
            ->get();

        $data = [];

        if ($infestaciones->isNotEmpty()) {

            // === SUMAS POR METODO ===
            $data[$tipo . '_kg_totales_madre'] = $infestaciones->sum('kg_madres');
            $data[$tipo . '_kg_madre_infestador_carton'] = $infestaciones->where('metodo', 'carton')->sum('kg_madres');
            $data[$tipo . '_kg_madre_infestador_tubos'] = $infestaciones->where('metodo', 'tubo')->sum('kg_madres');
            $data[$tipo . '_kg_madre_infestador_mallita'] = $infestaciones->where('metodo', 'malla')->sum('kg_madres');

            // === CANTIDAD DE INFESTADORES ===
            $inf_carton = $infestaciones->where('metodo', 'carton')->sum('infestadores');
            $inf_tubo = $infestaciones->where('metodo', 'tubo')->sum('infestadores');
            $inf_malla = $infestaciones->where('metodo', 'malla')->sum('infestadores');

            $data[$tipo . '_cantidad_infestadores_carton'] = $inf_carton;
            $data[$tipo . '_cantidad_infestadores_tubos'] = $inf_tubo;
            $data[$tipo . '_cantidad_infestadores_mallita'] = $inf_malla;

            // === PROCEDENCIA ===
            $lista = $infestaciones
                ->groupBy('campo_origen_nombre')
                ->map(fn($grupo) => [
                    'campo_origen_nombre' => $grupo->first()->campo_origen_nombre,
                    'kg_madres' => $grupo->sum('kg_madres'),
                ])
                ->values()
                ->toArray();

            $data[$tipo . '_procedencia_madres'] = json_encode($lista);

            // === MADRES POR INFESTADOR ===
            $data[$tipo . '_cantidad_madres_por_infestador_carton'] =
                $inf_carton > 0 ? $data[$tipo . '_kg_madre_infestador_carton'] / $inf_carton : 0;

            $data[$tipo . '_cantidad_madres_por_infestador_tubos'] =
                $inf_tubo > 0 ? $data[$tipo . '_kg_madre_infestador_tubos'] / $inf_tubo : 0;

            $data[$tipo . '_cantidad_madres_por_infestador_mallita'] =
                $inf_malla > 0 ? $data[$tipo . '_kg_madre_infestador_mallita'] / $inf_malla : 0;

            // Número de pencas (del brote piso del campo)
            $data[$tipo . '_numero_pencas'] = $campania->brotexpiso_actual_total_brotes_2y3piso;
        } else {
            // No hay infestaciones → reset general
            $data = [
                $tipo . '_kg_totales_madre' => 0,
                $tipo . '_kg_madre_infestador_carton' => 0,
                $tipo . '_kg_madre_infestador_tubos' => 0,
                $tipo . '_kg_madre_infestador_mallita' => 0,
                $tipo . '_cantidad_infestadores_carton' => 0,
                $tipo . '_cantidad_infestadores_tubos' => 0,
                $tipo . '_cantidad_infestadores_mallita' => 0,
                $tipo . '_procedencia_madres' => json_encode([]),
                $tipo . '_cantidad_madres_por_infestador_carton' => 0,
                $tipo . '_cantidad_madres_por_infestador_tubos' => 0,
                $tipo . '_cantidad_madres_por_infestador_mallita' => 0,
                $tipo . '_numero_pencas' => null,
            ];
        }

        // 4. Guardar usando la función reutilizable
        $this->actualizarMetricas($campaniaId, $data);
    }

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
        if ($campania->distribucionesCostosMensuales()->exists()) {
            throw new \Exception("No se puede eliminar la campaña porque tiene distribuciones de costos mensuales registradas.");
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