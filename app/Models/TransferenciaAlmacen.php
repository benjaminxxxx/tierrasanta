<?php

// app/Models/TransferenciaAlmacen.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TransferenciaAlmacen extends Model
{
    use HasFactory;

    protected $table = 'transferencias_almacen';

    protected $fillable = [
        'producto_id',
        'almacen_origen_id',
        'almacen_destino_id',
        'cantidad',
        'fecha_transferencia',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }
}
