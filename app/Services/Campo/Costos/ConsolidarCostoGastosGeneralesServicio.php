<?php

namespace App\Services\Campo\Costos;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Services\Campania\Data\DataCostoServicio;
use DB;

class ConsolidarCostoGastosGeneralesServicio
{
    public function consolidarGastosGenerales(CampoCampania $campania): int
    {
        $filas = app(DataCostoServicio::class)->generarCostoPor($campania->id);
 
        DB::transaction(function () use ($campania, $filas) {
            // Solo borra lo que este proceso regenera: costo_fijo y costo_operativo
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->whereIn('origen_tipo', ['costo_fijo', 'costo_operativo'])
                ->delete();

            $ahora = now();
    
            $filasParaInsertar = collect($filas)->map(function ($f) use ($ahora) {
            
                return [
                    'campania' => $f['campania'],
                    'fecha' => $f['fecha'],
                    'origen_tipo' => $f['tipo_gasto'] === 'Costo Fijo' ? 'costo_fijo' : 'costo_operativo',
                    'origen_id' => $f['origen_id'],
                    'campo' => $f['campo'],
                    'labor' => null,
                    'labor_nombre' => $f['detalle_labor'],
                    'trabajador' => null,
                    'cuadrilla_grupo_id' => null,
                    'tipo_cambio' => 1.0000,
                    'minutos' => null,
                    'cantidad_jornales' => null,
                    'insumo_nombre' => null,
                    'orden_compra' => null,
                    'factura' => null,
                    'tienda_comercial' => null,
                    'cantidad_insumo' => null,
                    'costo_total' => $f['costo'],
                    'observacion' => $f['observacion'],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            })->toArray();

            foreach (array_chunk($filasParaInsertar, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return count($filas);
    }
}