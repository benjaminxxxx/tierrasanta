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
        'stock_actual',
        'costo_unitario_promedio',

        'tipo_compra_codigo_inicial',
        'serie_inicial',
        'numero_inicial'
    ];
    protected $casts = [
        'stock_actual' => 'float',
        'costo_unitario_promedio' => 'float',
    ];
    public function comprobante()
    {
        return $this->belongsTo(SunatTabla10TipoComprobantePago::class, 'tipo_compra_codigo_inicial');
    }
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
    public function getComprobanteTextoAttribute()
    {
        if (!$this->tipo_compra_codigo_inicial || !$this->serie_inicial || !$this->numero_inicial) {
            return null;
        }

        $descripcion = $this->comprobante->descripcion ?? 'Comprobante';

        return "{$descripcion}: {$this->serie_inicial}-{$this->numero_inicial}";
    }
}
