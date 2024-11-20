<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraProducto extends Model
{
    use HasFactory;
    protected $fillable = [
        'producto_id',
        'tienda_comercial_id',
        'fecha_compra',
        'orden_compra',
        'factura',
        'costo_por_kg',
        'total',
        'stock',
        'fecha_termino',
        'estado',
        
        'tipo_compra_codigo',
        'serie',
        'numero',
        'tabla12_tipo_operacion',
        'tipo_kardex'
    ];

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    public function getCantidadDisponibleAttribute()
    {
        return (float)$this->stock -  (float)$this->almacenSalida()->sum('stock');
    }
    public function almacen()
    {
        return $this->hasMany(AlmacenProductoSalida::class, 'compra_producto_id');
    }
    public function almacenSalida()
    {
        return $this->hasMany(CompraSalidaStock::class, 'compra_producto_id');
    }

    // Relación con TiendaComercial
    public function tiendaComercial()
    {
        return $this->belongsTo(TiendaComercial::class, 'tienda_comercial_id');
    }
}
