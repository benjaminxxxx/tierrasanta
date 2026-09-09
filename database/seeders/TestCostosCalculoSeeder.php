<?php

namespace Database\Seeders;

use App\Services\GeneradorPlanillaMensualSimuladaService;
use App\Services\Simulacion\GeneradorRiegoDetalladoSimuladoService;
use App\Services\Simulacion\GeneradorRiegoDiarioSimuladoService;
use App\Services\Simulacion\SeleccionadorRegadoresSimuladosServicio;
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

        $this->command->info('Generando riego diario de regadores fijos...');
        $regadores = app(SeleccionadorRegadoresSimuladosServicio::class)->seleccionar(4);
        $camposDisponibles = app(SeleccionadorRegadoresSimuladosServicio::class)->camposDisponibles();
        app(GeneradorRiegoDetalladoSimuladoService::class)->generarParaMes($regadores,$camposDisponibles, 2026, 8);


        $this->command->info('Generando planilla mensual proyectada...');
        $respuestaPlanilla = $generador2->generarPlanillaMensual(2026, 8);

        $this->command->info('Consolidando costo de mano de obra y BDD Mensual en las campañas...');
        $generadorConsolidacion->consolidarPlanillaCampaniasMasivo();

        $this->command->info('Validando montos y consistencia...');
        // Quedo a la espera de las indicaciones para el cálculo de validación final.
    }
}