<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class GastoAdicionalPorGrupoCuadrilla extends Model
{
    use HasFactory;

    // Tabla asociada al modelo (opcional si el nombre sigue las convenciones)
    protected $table = 'cuad_gastos_grupos';

    // Campos asignables masivamente

    protected $fillable = [
        'monto',
        'descripcion',
        'anio_contable',
        'mes_contable',
        'fecha_gasto',
        'codigo_grupo',
        'cuad_tramo_laboral_id',
        'estado',
        'creado_por',
        'aprobado_por',
        'aprobado_en',
        'habilitado_por',
        'habilitado_en',
        'esta_pagado',
        'desglose_detalle_id'
    ];

    protected $casts = [
        'fecha_gasto' => 'datetime',
        'aprobado_en' => 'datetime',
        'habilitado_en' => 'datetime',
        'esta_pagado' => 'boolean'
    ];

    protected function descripcion(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn(string $value) => mb_strtoupper(trim($value)),
        );
    }

    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo', 'codigo');
    }
    public function desgloseDetalle()
    {
        return $this->belongsTo(DesgloseDetalle::class, 'desglose_detalle_id');
    }
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
    public function habilitadoPor()
    {
        return $this->belongsTo(User::class, 'habilitado_por');
    }
    // ── Scopes ───────────────────────────────────────────────────
    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }
    public function scopeAprobado($query)
    {
        return $query->where('estado', 'aprobado');
    }
    public function scopeEnCorreccion($query)
    {
        return $query->where('estado', 'en_correccion');
    }
    // ── Helpers de estado ─────────────────────────────────────────
    public function estaEditablePor(User $user): bool
    {
        if ($this->estado === 'aprobado') {
            return false;
        }
        // Pendiente: solo el creador o si no tiene creador asignado
        if ($this->estado === 'pendiente') {
            return is_null($this->creado_por) || $this->creado_por === $user->id;
        }
        // En corrección: cualquier usuario autenticado puede editar
        return true;
    }

    public function estaEliminablePor(User $user): bool
    {
        // Los aprobados NUNCA se pueden eliminar
        if ($this->estado === 'aprobado') {
            return false;
        }
        return $this->estaEditablePor($user);
    }
    public function getFechaContableAttribute()
    {
        return "{$this->mes_contable}-{$this->anio_contable}";
    }
    /**
     * Detecta si la fecha del gasto está fuera del rango del tramo.
     */
    public function tieneFechaFueraDeRango(): bool
    {
        if (!$this->tramo) {
            return false;
        }
        $fecha = Carbon::parse($this->fecha_gasto);
        $inicio = Carbon::parse($this->tramo->fecha_inicio)->startOfDay();
        $fin = Carbon::parse($this->tramo->fecha_fin)->endOfDay();

        return !$fecha->between($inicio, $fin);
    }
    //deprecado
    public function tramo()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'cuad_tramo_laboral_id');
    }
    public function tramoLaboral()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'cuad_tramo_laboral_id');
    }
}
