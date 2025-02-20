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
        'maquinaria_id',
        'indice', //cuando se agregan mas de un registro a la vez, es importante saber el orden para que el kardex lo haga igual
        'tipo_kardex',
        'registro_carga'
    ];
    
    public function kardexProducto()
    {
        return $this->belongsTo(KardexProducto::class, 'kardex_producto_id');
    }
    public function compraStock()
    {
        return $this->hasMany(CompraSalidaStock::class, 'salida_almacen_id');
    }
 
    public function compraSalida()
    {
        return $this->hasManyThrough(
            CompraProducto::class,         // Modelo destino
            CompraSalidaStock::class,      // Modelo intermedio
            'salida_almacen_id',           // Clave foránea en CompraSalidaStock (relación con AlmacenProductoSalida)
            'id',                           // Clave primaria en CompraProducto
            'id',                           // Clave primaria en AlmacenProductoSalida
            'compra_producto_id'            // Clave foránea en CompraSalidaStock (relación con CompraProducto)
        );
    }
    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con Compra
    /**
     * Esta funcion debe quedar obsoleta, la compra ya no se relaciona a compra_proucto_id, sino a salidacomprastock que aun falta verificar, para su correcto uso cambia por compraSalida
     */
    public function compra()
    {
        return $this->belongsTo(CompraProducto::class, 'compra_producto_id');
    }
    
    public function maquinaria()
    {
        return $this->belongsTo(Maquinaria::class, 'maquinaria_id');
    }
    public function getMaquinaNombreAttribute()
    {
        $tipoKardex = $this->tipo_kardex;
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
        return $this->tipo_kardex=='negro'?'No registra contabilidad':'';
    }
}
