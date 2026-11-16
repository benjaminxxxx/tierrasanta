<?php

namespace App\Services\Producto;

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

    // LEGACY: encontrar() y kardexAgrupadosPorAnio() solo los usaba "Kardex por insumo" (/almacen/kardex/crear);
    // están en legacy/app/Services/Producto/ProductoServicioKardexPorInsumo.php

    protected $productoId;
    protected $producto;

    // LEGACY: actualizarCompra() y registrarCompraProducto() (tabla compra_productos) se movieron
    // sin cambios a legacy/app/Services/ProductoServicioCompras.php
}
