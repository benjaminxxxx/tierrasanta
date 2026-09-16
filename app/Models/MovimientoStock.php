<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'direccion',
        'producto_id',
        'cantidad',
        'fecha_movimiento',
        'motivo',
        'almacen_id',
        'origen_type',
        'origen_id',
        'tipo_kardex'
    ];

    /**
     * Relación con el producto.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Relación con el almacén.
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    /**
     * Relación polimórfica para el origen del movimiento (ej. Ventas, Compras, Ajustes).
     */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }
}