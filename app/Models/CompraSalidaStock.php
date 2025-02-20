<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraSalidaStock extends Model
{
    use HasFactory;
    protected $table = 'compra_salida_stock';

    protected $fillable = [
        'compra_producto_id',
        'salida_almacen_id',
        'kardex_producto_id',
        'stock'
    ];

    public function salida(){
        return $this->belongsTo(AlmacenProductoSalida::class,"salida_almacen_id");
    }
    public function compra(){
        return $this->belongsTo(CompraProducto::class,"compra_producto_id");
    }
  
}
