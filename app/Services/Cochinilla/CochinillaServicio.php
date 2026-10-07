<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaIngreso;
use App\Models\VentaCochinilla;
use Illuminate\Support\Carbon;

class CochinillaServicio
{
    /**
     * Obtiene los ingresos recientes de cochinilla según filtros básicos.
     *
     * @param array $filtro ['filtroVenteado' => ..., 'filtroFiltrado' => ..., 'campaniaSeleccionado' => ..., 'fecha' => ..., 'tolerancia' => ...]
     * @return array
     */
    public static function IngresoCochinillaParaVenta($fecha, $tipo_ingreso = 'filtrados', $tolerancia = 10)
    {
        $query = CochinillaIngreso::with([
            'detalles',
            'campoCampania',
            'detalles.observacionRelacionada',
            'venteados',
            'filtrados',
        ]);

        // Filtro por filtrado
        if (!empty($tipo_ingreso)) {
            if ($tipo_ingreso === 'filtrados') {
                $query->whereHas('filtrados');
            } elseif ($tipo_ingreso === 'sinfiltrados') {
                $query->whereDoesntHave('filtrados');
            }
        }

        // Filtro por fecha de ingreso con tolerancia
        $fechaReferencia = isset($fecha) ? Carbon::parse($fecha) : now();

        $query->whereDate('fecha', '<=', $fechaReferencia)
            ->whereDate('fecha', '>=', $fechaReferencia->copy()->subDays($tolerancia));

        return $query->orderBy('fecha', 'desc')
            ->get()
            // La mamá que fue a infestar no se vende: vuelve como ingreso de infestadores (sería vender dos veces)
            ->filter(fn($ingreso) => $ingreso->kg_vendibles > 0)
            ->values()
            ->map(function ($ultimoIngreso) {
                $noVendible = round((float) $ultimoIngreso->total_kilos - $ultimoIngreso->kg_vendibles, 2);
                return [
                    'ingreso_id' => $ultimoIngreso->id,
                    // El dueño de la cochinilla (en la de infestadores, el campo de origen; no donde estaban las cajitas)
                    'campo' => $ultimoIngreso->campo_origen ?: $ultimoIngreso->campo,
                    'fecha_ingreso' => $ultimoIngreso->fecha,
                    'fecha_filtrado' => $ultimoIngreso->fecha_proceso_filtrado,
                    'cantidad_fresca' => $ultimoIngreso->total_kilos,
                    'cantidad_seca' => $ultimoIngreso->total_filtrado_primera,
                    'procedencia' => $ultimoIngreso->observacion,
                    'venta_cantidad' => null,
                    'venta_condicion' => null,
                    'venta_cliente' => null,
                    'venta_item' => null,
                    'venta_fecha' => null,
                    'detalle' => "Campo: " . ($ultimoIngreso->campo_origen ?: $ultimoIngreso->campo)
                        . ($ultimoIngreso->campos_infestados ? " (infestadores recogidos en {$ultimoIngreso->campos_infestados})" : '')
                        . "\nFecha Ingreso: $ultimoIngreso->fecha\nFecha Filtrado: $ultimoIngreso->fecha_proceso_filtrado\nCant. fresca: $ultimoIngreso->total_kilos"
                        . ($noVendible > 0 ? "\nNo vendible (fue a infestación): {$noVendible} kg" : ''),
                    'detalle_stock' => "Cant. Seca: $ultimoIngreso->total_filtrado_primera\nCant. Vendida: $ultimoIngreso->cantidad_vendida"
                ];
            })->toArray();
    }
   

}