<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioCampo extends Model
{
    protected $table = 'servicios_campo';

    public const COMPROBANTES = [
        'factura' => 'Factura',
        'boleta' => 'Boleta',
        'nota_venta' => 'Nota de venta',
    ];

    public const TIPOS_COSTO = [
        'blanco' => 'Blanco (formal)',
        'negro' => 'Negro (informal)',
    ];

    public const UNIDADES_SUGERIDAS = ['hora', 'kilo', 'tonelada'];

    protected $fillable = [
        'servicio',
        'unidad',
        'costo_unitario',
        'tipo_comprobante',
        'numero_comprobante',
        'tipo_costo',
        'fecha_comprobante',
        'porcentaje_igv',
        'cantidad_total',
        'subtotal',
        'igv',
        'total',
        'costo_total',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha_comprobante' => 'date',
        'costo_unitario' => 'decimal:6',
        'porcentaje_igv' => 'decimal:2',
        'cantidad_total' => 'decimal:3',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'costo_total' => 'decimal:2',
    ];

    public function detalles()
    {
        return $this->hasMany(ServicioCampoDetalle::class, 'servicio_campo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getEsFacturaAttribute(): bool
    {
        return $this->tipo_comprobante === 'factura';
    }

    public function getComprobanteNombreAttribute(): string
    {
        return self::COMPROBANTES[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }
}
