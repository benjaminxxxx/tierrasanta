<?php

namespace App\Services\Simulacion;

use App\Models\CampoCampania;
use App\Models\PlanEmpleado;

class SeleccionadorRegadoresSimuladosServicio
{
    /**
     * Selecciona trabajadores ya creados por el generador de datos simulados
     * para asignarlos como regadores fijos de lunes a sábado.
     *
     * Retorna el mismo formato que usa
     * ReporteDiarioAgregarRegadoresComponent::agregarRegadores()
     * para $trabajadoresAgregados, así RiegoServicio::registrarRegadoresEnFecha()
     * lo puede consumir directo sin adaptar nada.
     */
    public function seleccionar(int $cantidad = 4): array
    {
        return PlanEmpleado::with('persona') // ⚠️ ajustar nombre real de la relación
            ->inRandomOrder()
            ->limit($cantidad)
            ->get()
            ->map(fn (PlanEmpleado $empleado) => [
                'nombre' => $empleado->persona->nombre_completo ?? "Empleado #{$empleado->id}", // ⚠️ ajustar accessor real
                'id' => $empleado->id,
                'tipo' => 'empleados',
            ])
            ->values()
            ->all();
    }
    public function camposDisponibles(){
        return CampoCampania::pluck('campo')->unique()->toArray();
    }
}