<?php

namespace Database\Seeders;

use App\Services\GeneradorPlanillaMensualSimuladaService;
use Illuminate\Database\Seeder;
use App\Services\GeneradorDatosSimuladosService;
use App\Services\GeneradorSimulacionService;
use App\Services\EvaluadorConsistenciaCostosService;

class TestCostosCalculoSeeder extends Seeder
{
    public function run(
        GeneradorDatosSimuladosService $generador,
        GeneradorPlanillaMensualSimuladaService $generador2,
        GeneradorSimulacionService $generadorConsolidacion
    ): void {

        $this->command->info('Generando trabajadores y asignaciones...');
        $generador->crearTrabajadoresContratadosEnPlanilla(50);
        $generador->asignarLaboresAleatoriasEnMes(2026, 8);

        $this->command->info('Generando planilla mensual proyectada...');
        $respuestaPlanilla = $generador2->generarPlanillaMensual(2026, 8);

        $this->command->info('Consolidando costo de mano de obra y BDD Mensual en las campañas...');
        $generadorConsolidacion->consolidarPlanillaCampaniasMasivo();
        
        $this->command->info('Validando montos y consistencia...');
        // Quedo a la espera de las indicaciones para el cálculo de validación final.
    }
}