<?php

namespace App\Services\Modulos\Planilla;

use App\Services\PlanillaServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use Illuminate\Support\Carbon;

class GestionPlanillaEmpleados
{
    public function guardarSueldosMasivos($cambios, $mesVigencia, $anioVigencia){

        app(PlanillaServicio::class)->guardarSueldosMasivos($cambios, $mesVigencia, $anioVigencia);
    }
    public function obtenerPlanillaAgrariaActual(){
        $mes  = Carbon::now()->format('m');
        $anio  = Carbon::now()->format('Y');
        return app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($mes,$anio);
    }
    public function obtenerEmpleadoPorUuid($uuid){
        return app(PlanillaEmpleadoServicio::class)->obtenerEmpleadoPorUuid($uuid);
    }
    public function eliminarEmpleado($uuid){
        app(PlanillaEmpleadoServicio::class)->eliminarEmpleado($uuid);
    }
    public function restaurarEmpleado($uuid){
        app(PlanillaEmpleadoServicio::class)->restaurarEmpleado($uuid);
    }
    public function guardarOrdenPlanilla($empleados){
        app(PlanillaEmpleadoServicio::class)->actualizarOrdenEmpleados($empleados);
    }
    
    // Métodos y propiedades genéricos para la gestión de empleados en la planilla

    public function guardarEmpleado($datos,$empleadoId = null)
    {
        if($empleadoId){
            return app(PlanillaEmpleadoServicio::class)->actualizarEmpleado($datos,$empleadoId);
        }else{
            return app(PlanillaEmpleadoServicio::class)->registrarEmpleado($datos);
        }
    }

    // Ejemplo de método genérico
    public function buscarEmpleado(array $filtros = [])
    {
        return app(PlanillaEmpleadoServicio::class)->buscarEmpleado($filtros);
    }

}