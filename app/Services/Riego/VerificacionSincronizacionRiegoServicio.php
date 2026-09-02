<?php

namespace App\Services\Riego;

use App\Models\ConsolidadoRiego;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use Illuminate\Support\Carbon;

class VerificacionSincronizacionRiegoServicio
{
    public function verificarPorFecha(string $fecha): array
    {
        $consolidados = ConsolidadoRiego::whereDate('fecha', $fecha)->get();
        $resultado = ['ok' => 0, 'desincronizados' => 0, 'omitidos' => 0];

        foreach ($consolidados as $item) {

            // Cuadrilla: "aún no desarrollado, innecesario por el momento" — se omite de la verificación
            if ($item->trabajador_type !== PlanEmpleado::class) {
                $resultado['omitidos']++;
                continue;
            }

            $mes = Carbon::parse($fecha)->month;
            $anio = Carbon::parse($fecha)->year;

            $detalleMensual = PlanMensualDetalle::where('plan_empleado_id', $item->trabajador_id)
                ->whereHas('planillaMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
                ->first();

            if (!$detalleMensual) {
                $item->update(['sincronizado' => false]);
                $resultado['desincronizados']++;
                continue;
            }

            $registroDiario = PlanRegistroDiario::where('plan_det_men_id', $detalleMensual->id)
                ->whereDate('fecha', $fecha)
                ->first();

            $horasEnPlanilla = $registroDiario->total_horas ?? 0;
            $horasEnRiego = round($item->minutos_jornal / 60, 2);

            $coincide = abs($horasEnPlanilla - $horasEnRiego) < 0.05; // tolerancia por redondeo

            $item->update(['sincronizado' => $coincide]);
            $coincide ? $resultado['ok']++ : $resultado['desincronizados']++;
        }

        return $resultado;
    }
}
