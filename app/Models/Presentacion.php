<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presentacion extends Model
{
    use HasFactory;

    protected $table = 'presentaciones';

    protected $fillable = [
        'producto_id',
        'unidad_medida_codigo',
        'nombre',
        'factor_conversion',
        'es_compra_defecto',
        'activo',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(SunatTabla6CodigoUnidadMedida::class, 'unidad_medida_codigo', 'codigo');
    }
}