<?php

namespace App\Services\Modulos\Planilla;

use App\Services\Handsontable\HSTPlanillaRegistroDiarioActividades;
use App\Services\PlanillaMensualServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;

class GestionPlanillaReporteDiario
{
    public function guardarRegistrosDiarios($fecha,$datos,$totalActividades){
        
        /*
        dd($datos);
        array:1 [▼ // app\Services\Modulos\Planilla\GestionPlanillaReporteDiario.php:13
  0 => array:6 [▼
    "plan_men_detalle_id" => 250
    "documento" => "29644292"
    "nombres" => "MAMANI MAMANI, ALEJANDRO"
    "asistencia" => "DM"
    "total_horas" => 8
    "total_bono" => ""
  ]
] 
  
dd($datos);
0 => array:14 [▼
    "plan_men_detalle_id" => 242
    "documento" => "29485873"
    "nombres" => "LOPE CHOQUEHUANCA, CIRILO"
    "asistencia" => "A"
    "total_horas" => 6
    "total_bono" => ""
    "campo_1" => "A2"
    "labor_1" => 69
    "entrada_1" => "6.00"
    "salida_1" => "10.00"
    "campo_2" => "FDM"
    "labor_2" => 143
    "entrada_2" => "10.00"
    "salida_2" => "12.00"
  ]*/
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