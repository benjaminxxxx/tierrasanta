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
        'estado'
    ];

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con TiendaComercial
    public function tiendaComercial()
    {
        return $this->belongsTo(TiendaComercial::class, 'tienda_comercial_id');
    }
}
