<?php

namespace App\Services\Campo\Costos;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use DB;

class ConsolidarCostoManoObraServicio
{
    /**
     * Se mantiene para no romper el botón actual "Consolidar" por campaña.
     * Internamente ya delega al método por rango.
     */
    public function consolidarPlanilla(CampoCampania $campania): int
    {
        return $this->consolidarPorRango(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );
    }
    /*
    public function consolidarPlanilla(CampoCampania $campania): int
    {
        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        DB::transaction(function () use ($campania, $filas) {
            // Solo se borra lo que este proceso sabe regenerar (planilla derivado de ella)
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->whereIn('origen_tipo', ['planilla'])
                ->delete();

            $ahora = now();
            $filasConTimestamps = collect($filas)->map(fn($f) => array_merge($f, [
                'tipo_cambio' => 1.0000,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]))->toArray();

            foreach (array_chunk($filasConTimestamps, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }*/
    /**
     * Consolida UNA campaña+campo, en un rango de fechas explícito
     * (puede ser el ciclo completo de la campaña, o un recorte de él).
     */
    public function consolidarPorRango(string $nombreCampania, string $campo, string $fechaInicio, ?string $fechaFin = null): int
    {
        $fechaFinEfectiva = $fechaFin ?? now()->format('Y-m-d');

        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $nombreCampania,
            $campo,
            $fechaInicio,
            $fechaFinEfectiva
        );

        DB::transaction(function () use ($nombreCampania, $campo, $fechaInicio, $fechaFinEfectiva, $filas) {
            // Acotado por campo + rango, no solo por campaña — así un
            // reconsolidado parcial no borra lo ya consolidado fuera de ese rango.
            ResumenCostoDiario::where('campania', $nombreCampania)
                ->where('campo', $campo)
                ->whereIn('origen_tipo', ['planilla'])
                ->whereBetween('fecha', [$fechaInicio, $fechaFinEfectiva])
                ->delete();

            $ahora = now();
            $filasConTimestamps = collect($filas)->map(fn($f) => array_merge($f, [
                'tipo_cambio' => 1.0000,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]))->toArray();

            foreach (array_chunk($filasConTimestamps, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }

    /**
     * Consolida TODAS las campañas activas que se traslapan con un rango
     * de fechas dado. Genérico a propósito: mes, trimestre, semestre o año
     * son todos "un rango" — no hay ningún concepto de "mes" hardcodeado aquí.
     */
    public function consolidarPlanillaEnRango(string $fechaInicio, string $fechaFin): int
    {
        $campanias = CampoCampania::where('fecha_inicio', '<=', $fechaFin)
            ->where(function ($q) use ($fechaInicio) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio);
            })
            ->get();
            
        $totalFilas = 0;

        foreach ($campanias as $campania) {
            $finCampaniaReal = $campania->fecha_fin ?? now()->format('Y-m-d');

            // Recorte: nunca antes del inicio real de la campaña,
            // nunca después de su fin real (o "hoy" si sigue abierta)
            $inicioEfectivo = max($campania->fecha_inicio, $fechaInicio);
            $finEfectivo = min($finCampaniaReal, $fechaFin);

            if ($inicioEfectivo > $finEfectivo) {
                continue; // no hay traslape real
            }

            $totalFilas += $this->consolidarPorRango(
                $campania->nombre_campania,
                $campania->campo,
                $inicioEfectivo,
                $finEfectivo
            );
        }

        return $totalFilas;
    }
}
