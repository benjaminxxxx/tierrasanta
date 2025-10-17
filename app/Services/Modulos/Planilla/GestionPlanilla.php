<?php

namespace App\Services\Modulos\Planilla;

use App\Services\PlanillaMensualServicio;

class GestionPlanilla
{
    public function generarPlanilla($params)
    {
        return app(PlanillaMensualServicio::class)->generarExcel($params);
    }
}