<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaIngreso;
use Illuminate\Support\Carbon;

class CochinillaServicio
{
    /**
     * Obtiene los ingresos recientes de cochinilla según filtros básicos.
     *
     * @param array $filtro ['filtroVenteado' => ..., 'filtroFiltrado' => ..., 'campaniaSeleccionado' => ..., 'fecha' => ..., 'tolerancia' => ...]
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function ultimosIngresos(array $filtro)
    {
        $query = CochinillaIngreso::with([
            'detalles',
            'campoCampania',
            'detalles.observacionRelacionada',
            'venteados',
            'filtrados',
        ]);

        // Filtro por venteado
        if (!empty($filtro['filtroVenteado'])) {
            if ($filtro['filtroVenteado'] === 'conventeado') {
                $query->whereHas('venteados');
            } elseif ($filtro['filtroVenteado'] === 'sinventeado') {
                $query->whereDoesntHave('venteados');
            }
        }

        // Filtro por filtrado
        if (!empty($filtro['filtroFiltrado'])) {
            if ($filtro['filtroFiltrado'] === 'confiltrado') {
                $query->whereHas('filtrados');
            } elseif ($filtro['filtroFiltrado'] === 'sinfiltrado') {
                $query->whereDoesntHave('filtrados');
            }
        }

        // Filtro por fecha de ingreso con tolerancia
        $fechaReferencia = isset($filtro['fecha']) ? Carbon::parse($filtro['fecha']) : now();
        $toleranciaDias = $filtro['tolerancia'] ?? 7;

        $query->whereDate('fecha', '<=', $fechaReferencia)
            ->whereDate('fecha', '>=', $fechaReferencia->copy()->subDays($toleranciaDias));

        return $query->orderBy('fecha', 'desc');
    }
}