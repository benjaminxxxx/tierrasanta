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
    /*se agregara en la nueva version soporte de tipos

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
    }*/
    public function consolidarPorRango(string $nombreCampania, string $campo, string $fechaInicio, ?string $fechaFin = null, ?array $tipos = null): int
    {
        $fechaFinEfectiva = $fechaFin ?? now()->format('Y-m-d');

        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $nombreCampania,
            $campo,
            $fechaInicio,
            $fechaFinEfectiva,
            $tipos
        );

        $origenesADelete = $this->resolverOrigenesTipo($tipos);

        DB::transaction(function () use ($nombreCampania, $campo, $fechaInicio, $fechaFinEfectiva, $filas, $origenesADelete) {
            ResumenCostoDiario::where('campania', $nombreCampania)
                ->where('campo', $campo)
                ->whereIn('origen_tipo', $origenesADelete) // ← solo borra lo del/los tipo(s) pedido(s)
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
    private function resolverOrigenesTipo(?array $tipos): array
    {
        $mapa = [
            'planilla' => ['planilla', 'cuadrilla'],           // horas directas + riego (ya existente)
            'bono_productividad' => ['planilla_bono_productividad'], // nuevo
        ];

        if ($tipos === null) {
            return array_merge(...array_values($mapa));
        }

        $origenes = [];
        foreach ($tipos as $tipo) {
            $origenes = array_merge($origenes, $mapa[$tipo] ?? []);
        }

        return $origenes;
    }

    /*se añadira en la nueva version $tipos para ser mas selectivo
    public function consolidarPlanillaEnRango(string $fechaInicio, string $fechaFin, ?array $tipos = null): int
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
    }*/
    public function consolidarPlanillaEnRango(string $fechaInicio, string $fechaFin, ?array $tipos = null): int
    {
        $campanias = CampoCampania::where('fecha_inicio', '<=', $fechaFin)
            ->where(function ($q) use ($fechaInicio) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaInicio);
            })
            ->get();

        $totalFilas = 0;

        foreach ($campanias as $campania) {
            $finCampaniaReal = $campania->fecha_fin ?? now()->format('Y-m-d');
            $inicioEfectivo = max($campania->fecha_inicio, $fechaInicio);
            $finEfectivo = min($finCampaniaReal, $fechaFin);

            if ($inicioEfectivo > $finEfectivo) {
                continue;
            }

            $totalFilas += $this->consolidarPorRango(
                $campania->nombre_campania,
                $campania->campo,
                $inicioEfectivo,
                $finEfectivo,
                $tipos
            );
        }

        return $totalFilas;
    }
}
