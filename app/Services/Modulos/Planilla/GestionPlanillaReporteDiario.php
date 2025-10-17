<?php

namespace App\Services\Modulos\Planilla;

use App\Services\Handsontable\HSTPlanillaRegistroDiarioActividades;
use App\Services\PlanillaMensualServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;

class GestionPlanillaReporteDiario
{
    public function guardarRegistrosDiarios($fecha,$datos,$totalActividades){
        return app(PlanillaRegistroDiarioServicio::class)->guardarRegistrosDiarios($fecha,$datos,$totalActividades);
        
    }
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