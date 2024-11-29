<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmacenProductoSalida extends Model
{
    use HasFactory;

    protected $fillable = [
        'item',
        'producto_id',
        'campo_nombre',
        'cantidad',
        'fecha_reporte',
        'compra_producto_id',
        'costo_por_kg',
        'total_costo',
        'cantidad_kardex_producto_id',
        'cantidad_stock_inicial',
        'kardex_producto_id',
        'maquinaria_id'
    ];
    

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con Compra
    public function compra()
    {
        return $this->belongsTo(CompraProducto::class, 'compra_producto_id');
    }
    public function kardexProducto()
    {
        return $this->belongsTo(KardexProducto::class, 'kardex_producto_id');
    }
    public function compraStock()
    {
        return $this->hasMany(CompraSalidaStock::class, 'salida_almacen_id');
    }
    public function maquinaria()
    {
        return $this->belongsTo(Maquinaria::class, 'maquinaria_id');
    }
    public function getMaquinaNombreAttribute()
    {
        $tipoKardex = $this->kardexProducto->kardex->tipo_kardex;
        if($tipoKardex=='blanco'){
            return $this->maquinaria?$this->maquinaria->alias_blanco:'-';
        }else{
            return $this->maquinaria?$this->maquinaria->nombre:'-';
        }
        
    }
    public function getPerteneceAUnaCompraAttribute()
    {
        return $this->compraStock()->count()>0;
    }
    
    public function getObservacionAttribute()
    {
        $kardexProducto = $this->kardexProducto()->first();
        if($kardexProducto){
            return $kardexProducto->kardex()->first()->tipo_kardex=='negro'?'No registra contabilidad':'';
        }
        return '';
    }
}
