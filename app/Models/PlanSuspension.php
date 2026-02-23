<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PlanSuspension extends Model
{
    protected $table = 'plan_suspensiones';
    
    protected $fillable = [
        'plan_empleado_id',
        'tipo_suspension_id',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'documento_respaldo',
        'creado_por',
        'actualizado_por',
    ];
    
    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
    ];
    
    protected $appends = ['esta_activa', 'duracion_dias'];
    
    // ==================== RELACIONES ====================
    
    /**
     * Empleado al que pertenece la suspensión
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }
    
    /**
     * Tipo de suspensión según código SUNAT
     */
    public function tipoSuspension(): BelongsTo
    {
        return $this->belongsTo(PlanTipoSuspension::class, 'tipo_suspension_id');
    }
    
    /**
     * Usuario que creó el registro
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
    
    /**
     * Usuario que actualizó el registro
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
    // Scopes útiles
    
    public function scopeEnRango($query, $fechaInicio, $fechaFin)
    {
        return $query->where(function($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
              ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
              ->orWhere(function($q2) use ($fechaInicio, $fechaFin) {
                  $q2->where('fecha_inicio', '<=', $fechaInicio)
                     ->where('fecha_fin', '>=', $fechaFin);
              });
        });
    }
    public function scopeDelMes($query, $mes, $anio)
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth();
        
        return $query->enRango($inicio, $fin);
    }
    public function getEstaActivaAttribute(): bool
    {
        return $this->fecha_inicio <= now() 
            && ($this->fecha_fin === null || $this->fecha_fin >= now());
    }
    
    public function getDuracionDiasAttribute(): int
    {
        $fin = $this->fecha_fin ?? now();
        return $this->fecha_inicio->diffInDays($fin)+1;
    }
}
