<?php

namespace App\Services\Planilla\Modulos;

use App\Services\Planilla\PlanillaMensualServicio;

class GestionPlanilla
{
    public function generarPlanilla($params)
    {
        return app(PlanillaMensualServicio::class)->generarExcel($params);
    }
}