<?php

namespace App\Services\Producto;

use App\Models\InsKardex;
use App\Models\Producto;

class ProductoServicio
{
    public function buscar(string $search = '', int $limite = 10): array
    {
        return Producto::withTrashed()
            ->orderBy('nombre_comercial')
            ->when(
                $search,
                fn($q) => $q
                    ->where('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('ingrediente_activo', 'like', "%{$search}%")
            )
            ->limit($limite)
            ->get(['id', 'nombre_comercial', 'ingrediente_activo', 'deleted_at'])
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->nombre_comercial
                    . ($p->ingrediente_activo ? " ({$p->ingrediente_activo})" : '')
                    . ($p->trashed() ? ' ⚠ eliminado' : ''),
            ])
            ->toArray();
    }

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

    protected $productoId;
    protected $producto;

    // LEGACY: actualizarCompra() y registrarCompraProducto() (tabla compra_productos) se movieron
    // sin cambios a legacy/app/Services/ProductoServicioCompras.php
}
