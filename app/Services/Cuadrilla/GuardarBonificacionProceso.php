<?php

namespace App\Services\Cuadrilla;

use App\Models\Actividad;
use App\Services\Planilla\RegistroDiario\ActividadMetodoServicio;
use App\Services\Planilla\Empleado\ActividadServicio;
use App\Services\Planilla\Empleado\EmpleadoServicio;
use DB;

class GuardarBonificacionProceso
{
    public static function ejecutar(Actividad $actividad, array $metodos, string $unidades, int $recojos,array $datos): void
    {
        // BDD de costos: regenerar la mano de obra de estas fechas al terminar la petición
        \App\Services\Costos\Consolidacion\BddManoObraServicio::registrarCambio((string) $actividad->fecha);
        DB::transaction(function () use ($actividad, $metodos, $unidades, $recojos, $datos) {

            // 1. Actualizar datos generales de la actividad
            ActividadServicio::actualizar([
                'unidades' => $unidades,
                'recojos' => $recojos
            ], $actividad->id);

            // 2. Sincronizar métodos y sus tramos
            $mapaMetodos = ActividadMetodoServicio::sincronizarMetodos($actividad, $metodos);
            // 3. Recalcular bonificaciones de empleados asignados
          
            EmpleadoServicio::guardarBonificaciones($actividad->id, $datos, $recojos, $mapaMetodos);
        });
    }
}