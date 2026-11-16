<?php

// LEGACY (14/11/2026): métodos que estaban en App\Services\Producto\ProductoServicio y solo usaba la página
// "Kardex por insumo" (/almacen/kardex/crear, InsumoKardexCrearComponent). Para restaurarlos, devolverlos a esa clase.

namespace App\Services\Producto;

use App\Models\InsKardex;
use App\Models\Producto;

trait ProductoServicioKardexPorInsumo
{
    public function encontrar(int $id): ?Producto
    {
        return Producto::withTrashed()
            ->with(['categoria', 'subcategoria', 'usos', 'tabla6', 'eliminador', 'nutrientes'])
            ->find($id);
    }

    /**
     * Retorna los InsKardex del producto agrupados por año.
     * Resultado: [ ['anio' => 2024, 'blanco' => InsKardex|null, 'negro' => InsKardex|null], ... ]
     */
    public function kardexAgrupadosPorAnio(Producto $producto): array
    {
        return InsKardex::where('producto_id', $producto->id)
            ->orderByDesc('anio')
            ->get(['id', 'producto_id', 'anio', 'tipo', 'estado'])
            ->groupBy('anio')
            ->map(fn($grupo, $anio) => [
                'anio' => $anio,
                'blanco' => $grupo->firstWhere('tipo', 'blanco'),
                'negro' => $grupo->firstWhere('tipo', 'negro'),
            ])
            ->values()
            ->toArray();
    }
}
