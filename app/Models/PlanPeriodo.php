<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanPeriodo extends Model
{
    use SoftDeletes;

    protected $table = 'plan_periodos';

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'plan_empleado_id',
        'codigo',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',

        // Auditoría
        'motivo_modificacion',
        'modificado_por',
        'motivo_eliminacion',
        'eliminado_por',
    ];

    /**
     * Casts de tipos
     */
    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con empleado
     */
    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    /**
     * Usuario que modificó el periodo
     */
    public function modificadoPor()
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    /**
     * Usuario que eliminó el periodo
     */
    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }
    public function tipoAsistencia()
    {
        return $this->belongsTo(
            PlanTipoAsistencia::class,
            'codigo',
            'codigo'
        );
    }
    /**
     * Atributo calculado: total_dias
     * Diferencia en días entre fecha_fin y fecha_inicio
     */
    protected function totalDias(): Attribute
    {
        return Attribute::make(
            get: fn() =>
            ($this->fecha_inicio && $this->fecha_fin)
            ? $this->fecha_inicio->diffInDays($this->fecha_fin) + 1   // ← ajuste correcto
            : 0
        );
    }
    protected function tipoLabel(): Attribute
    {
        return Attribute::make(
            get: fn() =>
            $this->tipoAsistencia?->descripcion
            ?? $this->codigo
        );
    }
    protected function tipoColor(): Attribute
    {
        return Attribute::make(
            get: fn() =>
            $this->tipoAsistencia?->color
            ?? '#D1D5DB' // gris neutro fallback
        );
    }
}
