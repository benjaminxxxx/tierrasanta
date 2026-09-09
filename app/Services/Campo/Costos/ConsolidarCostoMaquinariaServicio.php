<?php

namespace App\Services\Campo\Costos;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Services\Campania\Data\DataInsumoServicio;
use DB;

class ConsolidarCostoMaquinariaServicio
{
    public function consolidarMaquinaria(CampoCampania $campania): int
    {
        $filas = app(DataInsumoServicio::class)->generarCostoMaquinariaPor(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        DB::transaction(function () use ($campania, $filas) {
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->where('origen_tipo', 'maquinaria')
                ->delete();

            $ahora = now();

            $filasParaInsertar = collect($filas)->map(fn($f) => [
                'campania' => $f['campania'],
                'fecha' => $f['fecha'],
                'origen_tipo' => 'maquinaria',
                'origen_id' => $f['origen_id'],
                'campo' => $f['campo'],
                'labor' => null,
                'labor_nombre' => $f['detalle_labor'],
                'trabajador' => $f['trabajador'],
                'cuadrilla_grupo_id' => null,
                'tipo_cambio' => 1.0000,
                'minutos' => $f['minutos'],
                'cantidad_jornales' => null,
                'insumo_nombre' => null,
                'orden_compra' => null,
                'factura' => null,
                'tienda_comercial' => null,
                'cantidad_insumo' => $f['cantidad'],
                'costo_total' => $f['costo'],
                'observacion' => $f['observacion'],
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->toArray();

            foreach (array_chunk($filasParaInsertar, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }
}