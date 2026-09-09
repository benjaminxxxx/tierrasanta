<?php

namespace App\Services\Campo\Costos;

use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Services\Campania\Data\DataInsumoServicio;
use DB;

class ConsolidarCostoInsumosServicio
{
    public function consolidarInsumos(CampoCampania $campania): int
    {
        $filas = app(DataInsumoServicio::class)->generarCostoInsumosPor(
            $campania->nombre_campania,
            $campania->campo,
            $campania->fecha_inicio,
            $campania->fecha_fin
        );

        DB::transaction(function () use ($campania, $filas) {
            // Borra ambos tipos derivados de esta fuente en una sola pasada
            ResumenCostoDiario::where('campania', $campania->nombre_campania)
                ->whereIn('origen_tipo', ['fertilizante', 'pesticida'])
                ->delete();

            $ahora = now();

            $filasParaInsertar = collect($filas)->map(fn($f) => [
                'campania' => $f['campania'],
                'fecha' => $f['fecha'],
                'origen_tipo' => $f['grupo_operativo'], // 'fertilizante' o 'pesticida', dinámico
                'origen_id' => $f['origen_id'],
                'campo' => $f['campo'],
                'labor' => null,
                'labor_nombre' => $f['detalle_labor'],
                'trabajador' => null,
                'cuadrilla_grupo_id' => null,
                'tipo_cambio' => 1.0000,
                'minutos' => null,
                'cantidad_jornales' => null,
                'insumo_nombre' => $f['detalle_labor'],
                'orden_compra' => null, // ya viene combinado en n_documento a nivel de lectura
                'factura' => null,
                'tienda_comercial' => $f['proveedor'],
                'cantidad_insumo' => $f['cantidad'],
                'costo_total' => $f['costo'],
                'observacion' => $f['n_documento'], // guardamos el documento combinado aquí temporalmente
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