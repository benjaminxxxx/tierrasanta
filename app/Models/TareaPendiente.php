<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TareaPendiente extends Model
{
    protected $table = 'tareas_pendientes';

    protected $fillable = [
        'tipo',
        'clave',
        'parent_id',
        'titulo',
        'descripcion',
        'variante',
        'cantidad_afectados',
        'estado',
        'servicio',
        'metodo_detectar',
        'acciones',
        'ejecutado_por',
        'ejecutado_en',
        'detectado_en',
    ];

    protected function casts(): array
    {
        return [
            'acciones' => 'array',
            'ejecutado_en' => 'datetime',
            'detectado_en' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subtareas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ejecutadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ejecutado_por');
    }
}