<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Cantidad producida en una actividad (fecha + campo + labor) por recojo: con bono (la ingresa bonificaciones) o sin
 * bono. Trabajador opcional (cuadrillero, empleado o ninguno). Se lee y escribe con Campo/Produccion.
 */
class ProduccionRegistro extends Model
{
    protected $table = 'produccion_registros';

    protected $fillable = ['actividad_id', 'cuadrillero_id', 'plan_empleado_id', 'numero_recojo', 'cantidad', 'unidad', 'con_bono',
        'observacion', 'creado_por', 'actualizado_por'];

    protected $casts = ['cantidad' => 'decimal:2', 'con_bono' => 'boolean', 'numero_recojo' => 'integer'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->creado_por ??= Auth::id());
        static::updating(fn(self $m) => $m->actualizado_por = Auth::id() ?? $m->actualizado_por);
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class, 'cuadrillero_id');
    }

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    /** cuadrilla | planilla | null */
    public function getTipoTrabajadorAttribute(): ?string
    {
        return $this->cuadrillero_id ? 'cuadrilla' : ($this->plan_empleado_id ? 'planilla' : null);
    }

    public function getNombreTrabajadorAttribute(): ?string
    {
        return $this->cuadrillero?->nombres ?? $this->empleado?->nombre_completo;
    }
}
