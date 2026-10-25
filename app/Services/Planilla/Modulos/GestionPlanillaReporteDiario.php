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
        return app(PlanillaMensualServicio::class)->obtenerPlanillaXFecha($fecha)->map(function ($empleado) use ($fecha) {
            // spp_snp y grupo de plan_mensual_detalles ya no se llenan (solo hasta 01/2026): se leen del contrato del mes
            $contrato = $empleado->empleado?->contratos->first();

            return [
                'id' => $empleado->plan_empleado_id,
                'nombres' => $empleado->nombres,
                'documento' => $empleado->documento,
                'orden' => $empleado->orden,
                'spp_snp' => $empleado->empleado?->pensionEn($fecha)?->plan_sp_codigo,
                'grupo' => $contrato?->asignacionEn($contrato->cubre($fecha) ? $fecha : null)?->grupo_codigo,
            ];
        });
    }
    public function obtenerPlanillaAgraria($mes,$anio)
    {    
        return app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($mes,$anio)
        ->map(function ($empleado) use ($mes, $anio) {
            $laboral = \App\Services\Planilla\Laboral\PlanillaLaboralConsulta::delMes($empleado, (int) $mes, (int) $anio);
            return [
                'id' => $empleado->id,
                'nombres' => $empleado->nombre_completo,
                'documento' => $empleado->documento,
                'orden' => $empleado->orden,
                'spp_snp' => $laboral['pension']?->plan_sp_codigo,
                'grupo' => $laboral['asignacion']?->grupo_codigo,
            ];
        });
    }
}