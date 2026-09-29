<?php

namespace App\Services\Planilla\Modulos;

use App\Services\Planilla\Handsontable\HSTPlanillaRegistroDiarioActividades;
use App\Services\Planilla\PlanillaMensualServicio;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioServicio;

class GestionPlanillaReporteDiario
{
    public function obtenerHandsontableObtenerRegistroDiarioPlanilla($fecha){   
        return app(HSTPlanillaRegistroDiarioActividades::class)->obtenerRegistroDiarioPlanilla($fecha);
    }
    public function guardarOrdenMensualEmpleados($mes,$anio,$listaPlanilla){
        
        app(PlanillaMensualServicio::class)->guardarOrdenMensualEmpleados($mes,$anio,$listaPlanilla);
        
    }
    public function obtenerPlanillaMensualXFecha($fecha)
    {
        return app(PlanillaMensualServicio::class)->obtenerPlanillaXFecha($fecha)->map(function ($empleado){

            return [
                'id' => $empleado->plan_empleado_id,
                'nombres' => $empleado->nombres,
                'documento' => $empleado->documento,
                'orden' => $empleado->orden,
                'spp_snp' => $empleado->spp_snp,
                'grupo' => $empleado->grupo
            ];
        });
    }
    public function obtenerPlanillaAgraria($mes,$anio)
    {    
        return app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($mes,$anio)
        ->map(function ($empleado){
            return [
                'id' => $empleado->id,
                'nombres' => $empleado->nombre_completo,
                'documento' => $empleado->documento,
                'orden' => $empleado->orden,
                'spp_snp' => $empleado->contratos[0]->plan_sp_codigo,
                'grupo' => $empleado->contratos[0]->grupo_codigo,
            ];
        });
    }
}