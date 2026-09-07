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
    public function obtenerEmpleadoPorUuid($id){
        return app(PlanillaEmpleadoServicio::class)->obtenerEmpleadoPorUuid($id);
    }
    public function eliminarEmpleado($id){
        app(PlanillaEmpleadoServicio::class)->eliminarEmpleado($id);
    }
    public function restaurarEmpleado($id){
        app(PlanillaEmpleadoServicio::class)->restaurarEmpleado($id);
    }
    public function guardarOrdenPlanilla($empleados){
        app(PlanillaEmpleadoServicio::class)->actualizarOrdenEmpleados($empleados);
    }
    
    

    // Ejemplo de método genérico
    public function buscarEmpleado(array $filtros = [])
    {
        return app(PlanillaEmpleadoServicio::class)->buscarEmpleado($filtros);
    }

}