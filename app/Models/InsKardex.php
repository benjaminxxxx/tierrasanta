<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsKardex extends Model
{
    protected $table = 'ins_kardexes';

    protected $fillable = [
        'producto_id',
        'descripcion',
        'codigo_existencia',
        'anio',
        'tipo',
        'stock_inicial',
        'costo_unitario',
        'costo_total',
        'stock_final',
        'costo_final',
        'estado',
        'metodo_valuacion',
        'file',
        'stock_actual',
        'costo_unitario_promedio',
        'tipo_compra_codigo_inicial',
        'serie_inicial',
        'numero_inicial',
        'closed_at',                // NUEVO: Fecha/hora exacta de cierre
        'creado_por',               // NUEVO: ID del usuario creador
        'editado_por',               // NUEVO

    ];

    protected $casts = [
        'stock_inicial' => 'float',
        'costo_unitario' => 'float',
        'costo_total' => 'float',
        'stock_actual' => 'float',
        'costo_unitario_promedio' => 'float',
        'stock_final' => 'float',
        'costo_final' => 'float',
        'anio' => 'integer',
    ];

    // Se especifica 'codigo' como la PK referenciada en la tabla de SUNAT
    public function comprobante()
    {
        return $this->belongsTo(SunatTabla10TipoComprobantePago::class, 'tipo_compra_codigo_inicial', 'codigo');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function movimientos()
    {
        return $this->hasMany(InsKardexMovimiento::class, 'kardex_id')
            ->orderBy('fecha')
            ->orderBy('id');
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
