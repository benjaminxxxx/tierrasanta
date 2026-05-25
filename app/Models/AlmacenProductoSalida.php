<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmacenProductoSalida extends Model
{
    use HasFactory;
    protected $table = 'almacen_producto_salidas';

    protected $fillable = [
        'item',
        'producto_id',
        'campo_nombre',
        'cantidad',
        'fecha_reporte',
        'costo_por_kg',
        'total_costo',
        'cantidad_kardex_producto_id',
        'cantidad_stock_inicial',
        'kardex_producto_id',
        'maquinaria_id',
        'indice', //cuando se agregan mas de un registro a la vez, es importante saber el orden para que el kardex lo haga igual
        'tipo_kardex',
        'registro_carga',
        'movimiento_id'
    ];
    public function kardexMovimiento()
    {
        return $this->belongsTo(InsKardexMovimiento::class, 'movimiento_id');
    }
    public function distribuciones()
    {
        return $this->hasMany(DistribucionCombustible::class, 'almacen_producto_salida_id');
    }
    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con Compra
    /**
     * Esta funcion debe quedar obsoleta, la compra ya no se relaciona a compra_proucto_id, sino a salidacomprastock que aun falta verificar
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

        if ($tipoKardex == 'blanco') {
            return (!empty($this->maquinaria?->alias_blanco))
                ? $this->maquinaria->alias_blanco
                : ($this->maquinaria?->nombre ?? '-');
        }

        return $this->maquinaria?->nombre ?? '-';
    }


    public function getObservacionAttribute()
    {
        return $this->tipo_kardex == 'negro' ? 'No registra contabilidad' : '';
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($registro) {
            $registro->distribuciones()->delete();
        });
    }
}
