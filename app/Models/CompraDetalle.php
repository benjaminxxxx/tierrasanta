<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'compra_detalles';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'presentacion_id',
        'nombre_presentacion',
        'cantidad',
        'costo_unitario',
        'porcentaje_descuento',
        'porcentaje_igv',
        'total_linea',
        'factor_conversion_usado',
        'cantidad_base',
        'costo_unitario_base',
        'costo_total_kardex'
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(Presentacion::class, 'presentacion_id');
    }
}