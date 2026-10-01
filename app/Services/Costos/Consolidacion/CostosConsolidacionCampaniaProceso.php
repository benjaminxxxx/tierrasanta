<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\CampoCampania;
use Illuminate\Support\Carbon;

/**
 * Regenera la BDD de costos (resumen_costo_diarios) de UNA campaña: las mismas fuentes y consolidadores que la
 * consolidación mensual (ConsolidadorCostosMensualesComponent::generarMes), pero filtrando por el campo de la
 * campaña y en su rango efectivo, en vez de todo el mes para todos los campos.
 *
 * - Mano de obra (planilla, cuadrilla, riego, bonos): consolidarPlanillaEnRango(..., campo).
 * - Servicios de campo y fertilizantes/pesticidas: consolidarEnRango(..., campo). Los insumos ponen al día antes
 *   los kardex desactualizados de esas salidas (si alguno no se puede actualizar, lanza el error).
 * - Maquinaria y gastos generales (costo fijo / operativo de /costos/mensual): ya son por campaña.
 * - Mano de obra indirecta: se reparte por mes entre todos los campos, así que se recalcula el mes completo
 *   (es rápida y determinista).
 *
 * No toca el resumen mensual (costo_mensuales) ni su Excel: eso sigue siendo la consolidación del mes.
 */
class CostosConsolidacionCampaniaProceso
{
    public function __construct(
        private ConsolidarCostoManoObraServicio $manoObra,
        private ConsolidarManoObraIndirectaServicio $indirecta,
        private ConsolidarCostoServiciosCampoServicio $servicios,
        private ConsolidarCostoInsumosServicio $insumos,
        private ConsolidarCostoMaquinariaServicio $maquinaria,
        private ConsolidarCostoGastosGeneralesServicio $gastosGenerales,
    ) {
    }

    /**
     * @return array{desde: string, hasta: string, mano_obra: int, servicios: int, insumos: int, maquinaria: int,
     *               gastos_generales: int, meses: int, avisos: string[]}
     */
    public function regenerar(int $campaniaId): array
    {
        @set_time_limit(600);
        $campania = CampoCampania::findOrFail($campaniaId);

        // Rango efectivo, como el consolidado: una campaña abierta termina donde empieza la siguiente
        $desde = $campania->fecha_inicio->toDateString();
        $hasta = min(
            ConsolidarCostoManoObraServicio::finEfectivoCampania($campania) ?? Carbon::today()->toDateString(),
            Carbon::today()->toDateString(),
        );

        $resultado = [
            'desde' => $desde,
            'hasta' => $hasta,
            'mano_obra' => $this->manoObra->consolidarPlanillaEnRango($desde, $hasta, null, $campania->campo),
            'servicios' => $this->servicios->consolidarEnRango($desde, $hasta, $campania->campo),
            'insumos' => $this->insumos->consolidarEnRango($desde, $hasta, $campania->campo),
            'maquinaria' => $this->maquinaria->consolidarMaquinaria($campania),
            'gastos_generales' => $this->gastosGenerales->consolidarGastosGenerales($campania),
            'meses' => 0,
            'avisos' => [],
        ];

        for ($mes = Carbon::parse($desde)->startOfMonth(); $mes->toDateString() <= $hasta; $mes->addMonth()) {
            $indirecta = $this->indirecta->consolidarMes($mes->year, $mes->month);
            $resultado['avisos'] = array_merge($resultado['avisos'], $indirecta['avisos'] ?? []);
            $resultado['meses']++;
        }

        return $resultado;
    }
}
