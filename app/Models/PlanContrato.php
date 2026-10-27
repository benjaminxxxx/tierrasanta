<?php

namespace App\Models;

use App\Services\Planilla\Laboral\PlanillaLaboralConsulta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Contrato (cabecera): periodo legal del empleado. Lo que cambia dentro del contrato está en sus partículas
 * (asignaciones y remuneraciones); qué partícula rige en una fecha lo resuelve PlanillaLaboralConsulta.
 */
class PlanContrato extends Model
{
    use SoftDeletes;

    public const TIPOS_PLANILLA = ['agraria', 'oficina', 'externa'];

    protected $fillable = [
        'plan_empleado_id', 'tipo_contrato', 'tipo_planilla', 'tipo_ingreso', 'fecha_inicio', 'fecha_fin', 'fecha_fin_prueba',
        'motivo_cese_sunat', 'comentario_cese', 'finalizado_por', 'creado_por', 'actualizado_por', 'eliminado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_fin_prueba' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(fn(self $c) => $c->creado_por ??= Auth::id());
        static::updating(fn(self $c) => $c->actualizado_por = Auth::id() ?? $c->actualizado_por);
    }

    // ------------------------------------------------------------------ relaciones

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(PlanContratoAsignacion::class, 'plan_contrato_id')->orderBy('desde');
    }

    public function remuneraciones()
    {
        return $this->hasMany(PlanContratoRemuneracion::class, 'plan_contrato_id')->orderBy('desde');
    }

    /** Documentos del contrato (sustento del cese: carta de renuncia, carta o acta de despido). */
    public function documentos()
    {
        return $this->hasMany(PlanContratoDocumento::class, 'plan_contrato_id')->orderByDesc('created_at');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function finalizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por');
    }

    public function eliminadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    // ------------------------------------------------------------------ lectura (delegada a la capa laboral)

    public function asignacionEn($fecha = null): ?PlanContratoAsignacion
    {
        return PlanillaLaboralConsulta::asignacionEn($this, $fecha);
    }

    public function remuneracionEn($fecha = null): ?PlanContratoRemuneracion
    {
        return PlanillaLaboralConsulta::remuneracionEn($this, $fecha);
    }

    public function asignacionDelMes(int $mes, int $anio): ?PlanContratoAsignacion
    {
        return $this->asignacionEn(PlanillaLaboralConsulta::fechaDelMes($mes, $anio, $this));
    }

    public function remuneracionDelMes(int $mes, int $anio): ?PlanContratoRemuneracion
    {
        return $this->remuneracionEn(PlanillaLaboralConsulta::fechaDelMes($mes, $anio, $this));
    }

    /** Asignación de hoy (o del último día del contrato si ya terminó). */
    public function getAsignacionActualAttribute(): ?PlanContratoAsignacion
    {
        return $this->asignacionEn();
    }

    public function getRemuneracionActualAttribute(): ?PlanContratoRemuneracion
    {
        return $this->remuneracionEn();
    }

    /** activo, en_prueba, finalizado o pendiente (calculado con las fechas). */
    public function getEstadoAttribute(): string
    {
        return PlanillaLaboralConsulta::estado($this);
    }

    public function cubre($fecha): bool
    {
        return PlanillaLaboralConsulta::cubre($this, $fecha);
    }

    public function esExterna(): bool
    {
        return $this->tipo_planilla === 'externa';
    }

    // ------------------------------------------------------------------ scopes

    /** Contratos que cruzan el rango de fechas. */
    public function scopeEnRango($query, $desde, $hasta)
    {
        return PlanillaLaboralConsulta::filtrarContratosEnRango($query, $desde, $hasta);
    }

    public function scopeVigentes($query, $fecha = null)
    {
        $f = $fecha ?? now();
        return PlanillaLaboralConsulta::filtrarContratosEnRango($query, $f, $f);
    }

    public function scopeFinalizados($query, $fecha = null)
    {
        return $query->whereNotNull('fecha_fin')->where('fecha_fin', '<', PlanillaLaboralConsulta::fecha($fecha)->toDateString());
    }

    public function estaVencido(): bool
    {
        return $this->fecha_fin && $this->fecha_fin->lt(now()->startOfDay());
    }

    public function proximoAVencer(): bool
    {
        return $this->fecha_fin && $this->fecha_fin->gte(now()->startOfDay()) && $this->fecha_fin->lt(now()->addDays(30));
    }
}
