<?php

// app/Models/StockProducto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockProducto extends Model
{
    use HasFactory;

    protected $table = 'stocks_productos';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'cantidad',
        'tipo_kardex'
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}