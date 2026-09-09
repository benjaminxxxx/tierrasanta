<?php

namespace App\Services;

use App\Models\CampoCampania;
use App\Services\Campo\Costos\ConsolidarCostoManoObraServicio;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Illuminate\Support\Facades\DB;
use Exception;
use Throwable;

class GeneradorSimulacionService
{
    /**
     * Consolida el costo de planilla (Mano de Obra) y genera la BDD Mensual
     * para todas las campañas activas/simuladas registradas.
     *
     */
    public function consolidarPlanillaCampaniasMasivo()
    {
        // Obtener todas las campañas creadas en el sistema
        $campanias = CampoCampania::all();

        if ($campanias->isEmpty()) {
            throw new Exception("No hay campañas registradas para consolidar.");
        }

        $consolidarManoObraServicio = app(ConsolidarCostoManoObraServicio::class);
        $campaniaServicio = app(CampaniaServicio::class);
        $consolidarManoObraServicio->consolidarPlanillaEnRango('2026-08-01', '2026-08-31');
        /*
                foreach ($campanias as $campania) {
                    $consolidarManoObraServicio->consolidarPlanilla($campania);
                    $campaniaServicio->generarBddMensual($campania->id);
                }*/
    }
}