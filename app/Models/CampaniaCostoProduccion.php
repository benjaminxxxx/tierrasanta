<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Versión (foto) de los costos de producción de una campaña. */
class CampaniaCostoProduccion extends Model
{
    protected $table = 'campania_costos_produccion';

    protected $fillable = [
        'campo_campania_id',
        'area',
        'tipo_cambio',
        'fecha_desde',
        'fecha_hasta',
        'total_soles',
        'filas_origen',
        'generado_por',
    ];

    protected $casts = [
        'area' => 'float',
        'tipo_cambio' => 'float',
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'total_soles' => 'float',
    ];

    public function campania(): BelongsTo
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CampaniaCostoProduccionDetalle::class, 'costo_produccion_id')->orderBy('orden');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
