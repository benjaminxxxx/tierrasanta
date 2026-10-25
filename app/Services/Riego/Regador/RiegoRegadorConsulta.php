<?php

namespace App\Services\Riego\Regador;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use Illuminate\Support\Carbon;

/**
 * Quiénes pueden regar en una fecha (para "Agregar regadores").
 *  - Planilla: solo empleados con contrato de planilla agraria vigente esa fecha.
 *  - Cuadrilla: cuadrilleros activos.
 *  - Habituales: los que más regaron en los últimos días (casi siempre son los mismos), para marcarlos rápido.
 * No se ofrece a quien ya está como regador esa fecha.
 */
class RiegoRegadorConsulta
{
    /** Días hacia atrás para decidir quiénes son los regadores habituales, y cuántos mostrar */
    public const DIAS_HABITUALES = 45;
    public const MAX_HABITUALES = 12;

    public const TIPOS = ['empleados' => PlanEmpleado::class, 'cuadrilleros' => Cuadrillero::class];

    /** @return array<int, array{id:int, name:string, tipo:string}> planilleros agrarios con contrato vigente esa fecha */
    public static function planilleros(string $fecha): array
    {
        $conContrato = PlanContrato::where('tipo_planilla', 'agraria')->enRango($fecha, $fecha)->pluck('plan_empleado_id')->unique();
        return PlanEmpleado::whereIn('id', $conContrato)
            ->whereNotIn('id', self::yaRegistrados($fecha, PlanEmpleado::class))
            ->alfabetico()->get()
            ->map(fn($e) => ['id' => $e->id, 'name' => $e->nombre_completo, 'tipo' => 'empleados'])->values()->all();
    }

    /** @return array<int, array{id:int, name:string, tipo:string}> cuadrilleros activos */
    public static function cuadrilleros(string $fecha): array
    {
        return Cuadrillero::whereNotIn('id', self::yaRegistrados($fecha, Cuadrillero::class))
            ->orderBy('nombres')->get(['id', 'nombres'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->nombres, 'tipo' => 'cuadrilleros'])->values()->all();
    }

    /**
     * Regadores habituales disponibles esa fecha (los que más días regaron antes de ella), con cuántos días regaron.
     *
     * @return array<int, array{id:int, name:string, tipo:string, dias:int}>
     */
    public static function habituales(string $fecha, ?array $planilleros = null, ?array $cuadrilleros = null): array
    {
        $disponibles = collect($planilleros ?? self::planilleros($fecha))->merge($cuadrilleros ?? self::cuadrilleros($fecha))
            ->keyBy(fn($t) => $t['tipo'] . ':' . $t['id']);
        $desde = Carbon::parse($fecha)->subDays(self::DIAS_HABITUALES)->toDateString();

        return ConsolidadoRiego::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<', $fecha)
            ->whereIn('trabajador_type', array_values(self::TIPOS))
            ->selectRaw('trabajador_type, trabajador_id, COUNT(DISTINCT fecha) as dias')
            ->groupBy('trabajador_type', 'trabajador_id')->orderByDesc('dias')->get()
            ->map(function ($r) use ($disponibles) {
                $t = $disponibles->get(array_search($r->trabajador_type, self::TIPOS) . ':' . $r->trabajador_id);
                return $t ? $t + ['dias' => (int) $r->dias] : null;
            })
            ->filter()->take(self::MAX_HABITUALES)->values()->all();
    }

    /** ids ya registrados como regadores esa fecha */
    private static function yaRegistrados(string $fecha, string $tipo): array
    {
        return ConsolidadoRiego::whereDate('fecha', $fecha)->where('trabajador_type', $tipo)->pluck('trabajador_id')->all();
    }
}
