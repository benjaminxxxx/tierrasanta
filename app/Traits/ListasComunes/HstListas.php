<?php

namespace App\Traits\ListasComunes;

use App\Models\Campo;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Models\SunatTabla10TipoComprobantePago;
use App\Models\Proveedor;

trait HstListas
{
    /**
     * Lista de productos
     * @return array
     */
    public function cargarListaHstCampos(){
        return Campo::get()
            ->map(fn($p) => ['id' => $p->nombre, 'label' => $p->nombre])
            ->toArray();
    }
    public function cargarListaHstMaquinarias(){
        return Maquinaria::get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre])
            ->toArray();
    }
    public function cargarListaHstProductos(){
        return Producto::get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre_comercial])
            ->toArray();
    }
    public function cargarListaHstProveedores(){
        // tienda_comercials quedó en legacy/: los proveedores viven en la tabla proveedores
        return Proveedor::with('persona')->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->razon_social ?: ($p->persona?->nombre_mostrar ?? '')])
            ->toArray();
    }
    public function cargarListaHstTipoDocumentos(){
        return SunatTabla10TipoComprobantePago::get()
            ->map(fn($p) => ['id' => $p->codigo, 'label' => $p->descripcion])
            ->toArray();
    }
}