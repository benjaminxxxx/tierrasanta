<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class CuadRegistroDiario extends Model
{
    use HasFactory;

    protected $table = 'cuad_registros_diarios';

    protected $fillable = [
        'cuadrillero_id',
        'fecha',
        'costo_personalizado_dia',
        'total_bono',
        'total_horas',
        'esta_pagado',
        'codigo_grupo',
        'tramo_laboral_id',
        'jornal_aplicado',
        'horas_destajo',
        'tramo_cuadrillero_id',
        'desglose_detalle_id'
    ];

    protected $appends = [
        'bonos_con_jornal',
        'bonos_sin_jornal',
        'total_pago_jornal'
    ];

    protected $casts = [
        'fecha' => 'date',
        'esta_pagado' => 'boolean',
        'se_paga_con_jornal' => 'boolean'
    ];

    /**
     * Calcula el costo base del jornal considerando solo horas NO a destajo.
     */
    public function getCostoDiaAttribute(): float
    {
        $jornal = $this->costo_personalizado_dia ?: (float) $this->jornal_aplicado;
        $horasJornaleadas = max(0, $this->total_horas - $this->horas_destajo);

        return ($horasJornaleadas / 8) * $jornal;
    }

    /**
     * Suma de bonos que están afectos a pagarse junto con el jornal.
     */
    public function getBonosConJornalAttribute(): float
    {
        return (float) $this->actividadesBonos
            ->where('se_paga_con_jornal', true)
            ->sum('total_bono');
    }

    /**
     * Suma de bonos independientes (NO se pagan con el jornal).
     */
    public function getBonosSinJornalAttribute(): float
    {
        return (float) $this->actividadesBonos
            ->where('se_paga_con_jornal', false)
            ->sum('total_bono');
    }

    /**
     * Total a pagar en la planilla/pago de Jornal (Costo día base + Bonos afectos a jornal).
     */
    public function getTotalPagoJornalAttribute(): float
    {
        return $this->costo_dia + $this->bonos_con_jornal;
    }

    /**
     * Costo total del día (Jornal base + Todos los bonos).
     */
    public function getTotalCostoAttribute(): float
    {
        return $this->costo_dia + $this->actividadesBonos->sum('total_bono');
    }

    // --- Relaciones ---

    public function actividadesBonos()
    {
        return $this->hasMany(CuadActividadBono::class, 'registro_diario_id');
    }

    public function desgloseDetalle()
    {
        return $this->belongsTo(DesgloseDetalle::class, 'desglose_detalle_id');
    }

    public function tramoLaboral()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'tramo_laboral_id');
    }

    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo');
    }

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }

    public function detalleHoras()
    {
        return $this->hasMany(CuadDetalleHora::class, 'registro_diario_id');
    }

    public function grupos()
    {
        return $this->hasManyThrough(
            CuaGrupo::class,
            CuadTramoLaboralGrupo::class,
            'cuad_tramo_laboral_id',
            'codigo',
            'tramo_laboral_id',
            'codigo_grupo'
        );
    }

    public function getCoincideTotalHorasAttribute()
    {
        $totalCalculado = $this->detalleHoras->reduce(function ($carry, $detalle) {
            $inicio = Carbon::createFromFormat('H:i:s', $detalle->hora_inicio);
            $fin = Carbon::createFromFormat('H:i:s', $detalle->hora_fin);
            return $carry + ($inicio->diffInMinutes($fin) / 60);
        }, 0);

        return round($this->total_horas, 2) === round($totalCalculado, 2);
    }
}