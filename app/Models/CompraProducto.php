<?php

namespace App\Models;

use Carbon\Carbon;
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
    public function almacenSalida()
    {
        return $this->hasMany(CompraSalidaStock::class, 'compra_producto_id');
    }
    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    public function getCantidadDisponibleAttribute()
    {
        return (float)$this->stock -  (float)$this->almacenSalida()->sum('stock');
    }
    public function getCostoPorUnidadAttribute()
    {
        return (float)$this->total / (float)$this->stock;
    }
    public function getCodigoComprobanteAttribute()
    {
        return "{$this->serie} - {$this->numero}";
    }


    // Relación con TiendaComercial
    public function tiendaComercial()
    {
        return $this->belongsTo(TiendaComercial::class, 'tienda_comercial_id');
    }

    public static function calcularCompras($mes, $anio, $tipoKardex, $esCombustible): float
    {
        // Calcular el rango de fechas para el mes y año dados
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        // Construir la consulta
        $query = self::where('tipo_kardex', $tipoKardex)
            ->whereBetween('fecha_compra', [$inicioMes, $finMes])
            ->get();

        if($esCombustible){
            $query->filter(function ($compra) {
                return $compra->producto && $compra->producto->esCombustibleProducto();
            });
        }

        // Retornar la suma de los totales
        return $query->sum('total');
    }
}
