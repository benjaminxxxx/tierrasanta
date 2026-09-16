<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compras';

    protected $fillable = [
        'proveedor_id',
        'almacen_id',
        'moneda',
        'tipo_cambio',
        'tipo_comprobante_codigo',
        'serie',
        'numero',
        'fecha_emision',
        'fecha_vencimiento',
        'forma_pago',
        'tipo_kardex',
        'subtotal_neto',
        'igv_total',
        'total',
        'notas',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];

    protected $casts = [
        'fecha_emision' => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
    }
}