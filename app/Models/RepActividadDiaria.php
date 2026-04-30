<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepActividadDiaria extends Model
{
    protected $table = 'rep_actividades_diarias';

    protected $fillable = [
        'fecha',
        'campo',
        'codigo_labor',
        'nombre_labor',
        'unidades',
        'recojos',
        'total_metodos',
        'total_planilla',
        'total_cuadrilla',
        'actividad_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'unidades' => 'integer',
        'recojos' => 'integer',
        'total_metodos' => 'integer',
        'total_planilla' => 'integer',
        'total_cuadrilla' => 'integer',
    ];

    // ── Scopes ───────────────────────────────────────────────────────
    public function scopeFecha($q, string $fecha)
    {
        return $q->whereDate('fecha', $fecha);
    }

    public function scopeCampo($q, string $campo)
    {
        return $q->where('campo', $campo);
    }

    public function scopeLabor($q, string $codigo)
    {
        return $q->where('codigo_labor', $codigo);
    }

    // ── Helpers ──────────────────────────────────────────────────────
    public function getTotalPersonasAttribute(): int
    {
        return $this->total_planilla + $this->total_cuadrilla;
    }
}