<?php
namespace App\Services\Planilla;

use App\Models\PlanContrato;
use Carbon\Carbon;

class ContratoStatsService
{
    public function getRecentContracts(int $limit = 5)
    {
        return PlanContrato::with(['empleado'])
            ->latest('fecha_inicio') // o ->latest() por id
            ->take($limit)
            ->get();
    }
    public function getStats(?int $mes = null, ?int $anio = null): array
    {
        // Si no se envían, toma el mes y año actual
        $fechaReferencia = Carbon::createFromDate(
            $anio ?? now()->year,
            $mes ?? now()->month,
            1
        );

        $inicioMes = $fechaReferencia->copy()->startOfMonth();
        $finMes = $fechaReferencia->copy()->endOfMonth();

        // 1. Contratos Activos en el mes/año seleccionado
        $statsActive = PlanContrato::query()
            ->where('fecha_inicio', '<=', $finMes)
            ->where(function ($q) use ($inicioMes) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $inicioMes);
            })
            ->where('estado', 'activo') // Ajustar según tus valores exactos de 'estado'
            ->count();

        // 2. En Período de Prueba
        $statsTrial = PlanContrato::query()
            ->whereNotNull('fecha_fin_prueba')
            ->where('fecha_fin_prueba', '>=', $fechaReferencia->copy()->startOfDay())
            ->where('estado', 'en_prueba') // O según la lógica de tu negocio
            ->count();

        // 3. Por Vencer en los próximos 30 días a partir de la fecha evaluada
        $statsExpiring = PlanContrato::query()
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [
                $fechaReferencia->copy()->startOfDay(),
                $fechaReferencia->copy()->addDays(30)->endOfDay()
            ])
            ->count();

        // 4. Finalizados / Cesados
        $statsTerminated = PlanContrato::query()
            ->where('estado', 'finalizado') // o donde 'motivo_cese_sunat' no sea null
            ->count();

        return compact('statsActive', 'statsTrial', 'statsExpiring', 'statsTerminated');
    }
}