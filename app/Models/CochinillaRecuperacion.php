<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tanda de infestación de un campo marcada como recuperada (ya no se esperan más infestadores). */
class CochinillaRecuperacion extends Model
{
    protected $table = 'cochinilla_recuperaciones';

    protected $fillable = ['campo', 'tanda_inicio', 'tanda_fin', 'campo_campania_id', 'kg_madres', 'kg_recuperados', 'nota', 'cerrada_por', 'cerrada_en'];

    protected $casts = ['tanda_inicio' => 'date', 'tanda_fin' => 'date', 'cerrada_en' => 'datetime', 'kg_madres' => 'float', 'kg_recuperados' => 'float'];

    public function cerradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }
}
