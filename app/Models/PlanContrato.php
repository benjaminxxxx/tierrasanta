<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanContrato extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'plan_empleado_id',
        'tipo_contrato',
        'fecha_inicio',
        'fecha_fin',
        'cargo_codigo',
        'grupo_codigo',
        'compensacion_vacacional',
        'tipo_planilla',
        'plan_sp_codigo',
        'esta_jubilado',
        'modalidad_pago',
        'motivo_despido',
        'creado_por',
        'actualizado_por',

        // Nuevos campos agregados
        'fecha_fin_prueba',
        'motivo_cese_sunat',
        'comentario_cese',
        'finalizado_por',
        'estado',
        'eliminado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'fecha_fin_prueba' => 'datetime',
        'esta_jubilado' => 'boolean',
        'compensacion_vacacional' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class,'plan_empleado_id');
    }
    public function descuento()
    {
        return $this->belongsTo(PlanDescuentoSp::class, 'plan_sp_codigo');
    }
    public function cargo()
    {
        return $this->belongsTo(PlanCargo::class, 'cargo_codigo');
    }
    public function grupo()
    {
        return $this->belongsTo(PlanGrupo::class, 'grupo_codigo');
    }

    /**
     * Relación con el usuario que creó el registro
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que actualizó el registro
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Relación con el usuario que finalizó el registro
     */
    public function finalizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por');
    }

    /**
     * Relación con el usuario que eliminó el registro
     */
    public function eliminadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    /**
     * Scope para contratos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para contratos finalizados
     */
    public function scopeFinalizados($query)
    {
        return $query->where('estado', 'finalizado');
    }

    /**
     * Scope para contratos renovados
     */
    public function scopeRenovados($query)
    {
        return $query->where('estado', 'renovado');
    }

    /**
     * Verifica si el contrato está vencido
     */
    public function estaVencido(): bool
    {
        return $this->fecha_fin < now();
    }

    /**
     * Verifica si el contrato está próximo a vencer (dentro de 30 días)
     */
    public function proximoAVencer(): bool
    {
        return $this->fecha_fin > now() && $this->fecha_fin < now()->addDays(30);
    }
}
