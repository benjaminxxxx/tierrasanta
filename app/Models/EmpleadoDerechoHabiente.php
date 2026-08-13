<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoDerechoHabiente extends Model
{
    use HasFactory;

    protected $table = 'empleado_derecho_habientes';

    protected $fillable = [
        'empleado_id',
        'derecho_habiente_id',
        'rol',
        'mes_vigencia',
        'anio_vigencia',
        'fecha_inicio_estudios',
        'fecha_fin_estudios',
        'activo',
        'motivo_inactivacion',
        'creado_por',
        'actualizado_por',
    ];
    protected $casts = [
        'activo' => 'boolean',
        'mes_vigencia' => 'integer',
        'anio_vigencia' => 'integer',
    ];
    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'empleado_id');
    }
    public function derechoHabiente()
    {
        return $this->belongsTo(DerechoHabiente::class);
    }

    /**
     * Usuario que creó la asignación.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Usuario que actualizó la asignación.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
