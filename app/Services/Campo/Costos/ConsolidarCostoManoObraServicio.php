<?php

namespace App\Services\Campo\Costos;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use DB;

class ConsolidarCostoManoObraServicio
{
    public function consolidarPlanilla(CampoCampania $campania): int
    {
        $filas = app(ConsolidarCostoPlanillaServicio::class)->generarFilas(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        DB::transaction(function () use ($campania, $filas) {
            // Solo se borra lo que este proceso sabe regenerar (planilla + riego derivado de ella)
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->whereIn('origen_tipo', ['planilla', 'riego'])
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
}
