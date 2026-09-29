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
        'fecha_inicio', // periodo de la tarea (null = sin periodo)
        'fecha_fin',
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
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * Tareas sin periodo (siempre visibles) o cuyo periodo se cruza con el rango dado.
     */
    public function scopeEnPeriodo($query, string $fechaInicio, string $fechaFin)
    {
        return $query->where(fn($q) => $q
            ->whereNull('fecha_inicio')
            ->orWhere(fn($q) => $q->where('fecha_inicio', '<=', $fechaFin)->where('fecha_fin', '>=', $fechaInicio)));
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