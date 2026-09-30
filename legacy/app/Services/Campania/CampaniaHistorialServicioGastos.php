<?php

namespace App\Services\Campania;

use App\Services\Cuadrilla\CuadrillaServicio;
use App\Services\Planilla\PlanillaSueldoGastoServicio;

/**
 * LEGACY: métodos que estaban en App\Services\Campania\CampaniaHistorialServicio (30/09/2026).
 * actualizarGastosyConsumos() no se llamaba desde ningún sitio; gastoPlanilla() y gastoCuadrilla() solo los usaba
 * ese método. gastoCuadrilla() llamaba a CuadrillaServicio::calcularGastoCuadrilla(), que usaba variables sin definir.
 * Para restaurarlos: devolverlos a CampaniaHistorialServicio, con CuadrillaServicio a app/Services/Cuadrilla y
 * calcularGastoPlanilla() (ver PlanillaSueldoGastoServicioGastos) a PlanillaSueldoGastoServicio.
 */
class CampaniaHistorialServicioGastos
{
    /**
     * Actualiza los Gastos y Consumos de una determinada campaña
     * @param int $campoCampaniaId
     */
    public function actualizarGastosyConsumos()
    {
        $this->campoCampania->update([
            'gasto_planilla' => $this->gastoPlanilla(),
            'gasto_cuadrilla' => $this->gastoCuadrilla()
        ]);
    }
    public function gastoPlanilla()
    {
        return PlanillaSueldoGastoServicio::calcularGastoPlanilla($this->campoCampaniaId);
    }
    public function gastoCuadrilla()
    {
        return CuadrillaServicio::calcularGastoCuadrilla($this->campoCampaniaId);
    }
}
