<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadMetodoTramo extends Model
{
    protected $table = 'actividad_metodo_tramos';
    protected $fillable = [
        'metodo_id',
        'hasta',
        'monto',
        'orden',
    ];
    public function metodo(): BelongsTo
    {
        return $this->belongsTo(ActividadMetodo::class, 'metodo_id');
    }
}
