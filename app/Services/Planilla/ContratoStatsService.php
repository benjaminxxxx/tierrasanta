<?php

namespace App\Services\Planilla;

use App\Models\PlanContrato;
use Carbon\Carbon;

/** Indicadores del panel de contratos. El estado se calcula con las fechas (capa laboral). */
class ContratoStatsService
{
    public function getRecentContracts(int $limit = 5)
    {
        return PlanContrato::with(['empleado', 'asignaciones.cargo', 'remuneraciones'])->latest('fecha_inicio')->take($limit)->get();
    }

    public function getStats(?int $mes = null, ?int $anio = null): array
    {
        $ref = Carbon::createFromDate($anio ?? now()->year, $mes ?? now()->month, 1);
        $inicioMes = $ref->copy()->startOfMonth();
        $finMes = $ref->copy()->endOfMonth();
        $hoy = now()->startOfDay();

        return [
            // Vigentes en algún momento del mes
            'statsActive' => PlanContrato::enRango($inicioMes, $finMes)->count(),
            // En periodo de prueba hoy
            'statsTrial' => PlanContrato::vigentes()->whereNotNull('fecha_fin_prueba')->where('fecha_fin_prueba', '>=', $hoy->toDateString())->count(),
            // Terminan en los próximos 30 días
            'statsExpiring' => PlanContrato::whereNotNull('fecha_fin')->whereBetween('fecha_fin', [$hoy->toDateString(), $hoy->copy()->addDays(30)->toDateString()])->count(),
            // Ya terminaron
            'statsTerminated' => PlanContrato::finalizados()->count(),
        ];
    }
}
