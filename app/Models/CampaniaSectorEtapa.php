<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Desde qué fecha un grupo de hileras está en una etapa (hasta que empieza la siguiente). */
class CampaniaSectorEtapa extends Model
{
    protected $table = 'campania_sector_etapas';

    protected $fillable = ['campania_sector_id', 'etapa', 'fecha_desde'];

    protected $casts = ['fecha_desde' => 'date'];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(CampaniaSector::class, 'campania_sector_id');
    }
}
