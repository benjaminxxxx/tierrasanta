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
            ->map(function ($ultimoIngreso) {
                return [
                    'ingreso_id' => $ultimoIngreso->id,
                    'campo' => $ultimoIngreso->campo,
                    'fecha_ingreso' => $ultimoIngreso->fecha,
                    'fecha_filtrado' => $ultimoIngreso->fecha_proceso_filtrado,
                    'cantidad_fresca' => $ultimoIngreso->total_kilos,
                    'cantidad_seca' => $ultimoIngreso->total_filtrado_primera,
                    'procedencia' => $ultimoIngreso->observacion,
                    'venta_cantidad' => null,
                    'venta_condicion' => 'venta',
                    'venta_cliente' => null,
                    'venta_item' => null,
                    'venta_fecha' => null,
                    'detalle' => "Campo: $ultimoIngreso->campo\nFecha Ingreso: $ultimoIngreso->fecha\nFecha Filtrado: $ultimoIngreso->fecha_proceso_filtrado\nCant. fresca: $ultimoIngreso->total_kilos",
                    'detalle_stock' => "Cant. Seca: $ultimoIngreso->total_filtrado_primera\nCant. Vendida: $ultimoIngreso->cantidad_vendida"
                ];
            })->toArray();
    }
   public static function obtenerInformacionDeVentaPorGrupo($registroEntregaGrupoId)
{
    return VentaCochinilla::where('grupo_venta', $registroEntregaGrupoId)
        ->orderBy('fecha_venta', 'desc')
        ->get()
        ->map(function ($ventaCochinilla) {
            $ingreso = $ventaCochinilla->ingreso;

            $campo = $ingreso?->campo ?? $ventaCochinilla->campo;
            $fechaFiltrado = $ingreso?->fecha_proceso_filtrado ?? $ventaCochinilla->fecha_filtrado;

            // Generar el detalle de forma más completa
            $detalle = $ingreso
                ? "Campo: {$ingreso->campo}\nFecha Ingreso: {$ingreso->fecha}\nFecha Filtrado: {$ingreso->fecha_proceso_filtrado}\nCant. fresca: {$ingreso->total_kilos}"
                : "Sin Ingreso vinculado\nCampo: {$ventaCochinilla->campo}\nFecha Filtrado: {$fechaFiltrado}";

            return [
                'ingreso_id'       => $ingreso?->id,
                'campo'            => $campo,
                'fecha_ingreso'    => $ingreso?->fecha,
                'fecha_filtrado'   => $fechaFiltrado,
                'cantidad_fresca'  => $ingreso?->total_kilos,
                'cantidad_seca'    => $ventaCochinilla->cantidad_seca,
                'procedencia'      => $ingreso?->observacion,
                'venta_cantidad'   => $ventaCochinilla->cantidad_seca,
                'venta_condicion'  => $ventaCochinilla->condicion,
                'venta_cliente'    => $ventaCochinilla->cliente,
                'venta_item'       => $ventaCochinilla->item,
                'venta_fecha'      => $ventaCochinilla->fecha_venta,
                'detalle'          => $detalle,
                'detalle_stock'    => $ingreso
                    ? "Cant. Seca: {$ingreso->total_filtrado_primera}\nCant. Vendida: {$ingreso->cantidad_vendida}"
                    : "-",
            ];
        })
        ->toArray();
}

}