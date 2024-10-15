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
        'total_costo'
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
/*
    // Calcula el total costo basado en la cantidad y el costo por kilogramo
    public function calcularTotalCosto()
    {
        return $this->cantidad * $this->costo_por_kg;
    }*/
    public function getCostoPorUnidadAttribute()
    {
        // Si tiene asignado un compra_producto_id
        if ($this->compra_producto_id && $this->compra) {
            return $this->compra->costo_por_kg; // Retorna el costo por kilogramo de la compra
        }
        return null; // Si no, retorna null
    }

    // Método para calcular el total costo basado en la cantidad y el costo por unidad
    public function getTotalCostoCalculadoAttribute()
    {
        // Si tiene asignado un compra_producto_id y la compra existe
        if ($this->compra_producto_id && $this->compra) {
            return $this->cantidad * $this->compra->costo_por_kg; // Calcula el total
        }
        return null; // Si no, retorna null
    }

    // Método para la observación basada en la existencia de la factura en CompraProducto
    public function getObservacionAttribute()
    {
        // Si tiene asignado un compra_producto_id y la compra existe
        if ($this->compra_producto_id && $this->compra) {
            // Si la factura es 'ng', retorna 'No registra contabilidad'
            if (mb_strtolower(trim($this->compra->factura)) === 'ng') {
                return 'No registra contabilidad';
            }
        }
        return ''; // Si no, retorna vacío
    }
}
