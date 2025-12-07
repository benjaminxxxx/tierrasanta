<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsKardex extends Model
{
    protected $table = 'ins_kardexes';

    /**
     * Campos que se pueden guardar masivamente
     */
    protected $fillable = [
        // Al crear el kardex
        'producto_id',
        'descripcion',
        'codigo_existencia',
        'anio',
        'tipo',
        'stock_inicial',
        'costo_unitario',
        'costo_total',

        // Campos que se completan después
        'stock_final',
        'costo_final',
        'estado',
        'metodo_valuacion',
        'file',
    ];
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    public function movimientos()
    {
        return $this->hasMany(InsKardexMovimiento::class, 'kardex_id')
            ->orderBy('fecha')
            ->orderBy('id'); // asegura orden cronológico
    }
}
