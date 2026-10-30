<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActividadMetodo extends Model
{
    protected $table = 'actividad_metodos';
    protected $fillable = [
        'actividad_id',
        'titulo',
        'estandar',
        'respeta_jornal',
        'orden',
    ];
    protected $casts = ['respeta_jornal' => 'boolean'];
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }
    public function tramos(): HasMany
    {
        return $this->hasMany(ActividadMetodoTramo::class, 'metodo_id')->orderBy('orden');
    }
}
