<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Base de las partículas laborales: un dato que cambia con el tiempo y solo tiene fecha "desde". Vale hasta la
 * siguiente fila del mismo dueño (o hasta el fin del contrato). Qué fila rige en una fecha lo decide
 * PlanillaLaboralConsulta::vigente(), nunca el código que la usa.
 */
abstract class PlanParticula extends Model
{
    protected $casts = ['desde' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (Auth::check()) {
                $m->creado_por ??= Auth::id();
            }
        });
        static::updating(function (self $m) {
            if (Auth::check()) {
                $m->actualizado_por = Auth::id();
            }
        });
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
