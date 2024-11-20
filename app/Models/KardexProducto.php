<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexProducto extends Model
{
    protected $fillable = [
        'kardex_id',         // Relación con el kardex principal
        'producto_id',       // Producto asociado
        'stock_inicial',     // Stock inicial al abrir el kardex
        'costo_unitario',    // Costo promedio inicial
        'costo_total',       // Costo total calculado
        'stock_final',       // Stock final al cerrar (nullable)
        'costo_final',       // Costo promedio final al cerrar (nullable)
        'estado',            // Estado del kardex del producto (activo o cerrado)
        'metodo_valuacion',  // Método de valuación (promedio o peps)
    ];
    public function kardex(){
        return $this->belongsTo(Kardex::class, 'kardex_id');
    }
    public function salidasStockUsado(){
        return $this->hasMany(AlmacenProductoSalida::class, 'cantidad_kardex_producto_id');
    }
    
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
