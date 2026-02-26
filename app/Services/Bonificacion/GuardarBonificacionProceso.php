<?php

namespace App\Services\Bonificacion;

use App\Models\Actividad;
use App\Services\ActividadMetodoServicio;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use App\Services\RecursosHumanos\Personal\EmpleadoServicio;
use DB;

class GuardarBonificacionProceso
{
    public static function ejecutar(Actividad $actividad, array $metodos, string $unidades, int $recojos,array $datos): void
    {
        DB::transaction(function () use ($actividad, $metodos, $unidades, $recojos, $datos) {

            // 1. Actualizar datos generales de la actividad
            ActividadServicio::actualizar([
                'unidades' => $unidades,
                'recojos' => $recojos
            ], $actividad->id);

            // 2. Sincronizar métodos y sus tramos
            $mapaMetodos = ActividadMetodoServicio::sincronizarMetodos($actividad, $metodos);
            // 3. Recalcular bonificaciones de empleados asignados
            //dd($mapaMetodos);
            EmpleadoServicio::guardarBonificaciones($actividad->id, $datos, $recojos, $mapaMetodos);
        });
    }
}