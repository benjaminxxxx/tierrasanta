<?php

namespace App\Services\Planilla\Modulos;

use App\Services\Planilla\Laboral\PlanillaLaboralProceso;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use Illuminate\Support\Carbon;

class GestionPlanillaEmpleados
{
    /** Nuevo sueldo desde el 1 del mes elegido: una remuneración nueva en el contrato de cada empleado (capa laboral). */
    public function guardarSueldosMasivos($cambios, $mesVigencia, $anioVigencia){
        if (!$mesVigencia || !$anioVigencia) {
            throw new \App\Exceptions\ErrorControlado('Debe seleccionar el mes y el año de vigencia.');
        }
        app(PlanillaLaboralProceso::class)->cambiarSueldos($cambios, sprintf('%04d-%02d-01', $anioVigencia, $mesVigencia));
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