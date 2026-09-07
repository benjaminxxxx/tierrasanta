<?php
namespace Database\Seeders;

use App\Services\GeneradorPlanillaMensualSimuladaService;
use Illuminate\Database\Seeder;
use App\Services\GeneradorDatosSimuladosService;
use App\Services\EvaluadorConsistenciaCostosService;

class TestCostosCalculoSeeder extends Seeder
{
    public function run(
        GeneradorDatosSimuladosService $generador,
        GeneradorPlanillaMensualSimuladaService $generador2
    ): void {
        
        $this->command->info('Generando trabajadores y asignaciones...');
        $generador->crearTrabajadoresContratadosEnPlanilla(50);
        $generador->asignarLaboresAleatoriasEnMes(2026, 8);
        $respuesta = $generador2->generarPlanillaMensual(2026, 8);

        $this->command->info('Validando montos y consistencia...');
        //$resultado = $evaluador->validarTotalesVersusDetalle(2026, 8);

        //if ($resultado->tieneDescuadres()) {
        //    $this->command->error("¡Atención! Hay un descuadre de: " . $resultado->getDiferencia());
        //} else {
        //    $this->command->info('✓ Todos los costos coinciden perfectamente.');
        
    }
}