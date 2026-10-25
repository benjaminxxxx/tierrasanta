<?php

namespace App\Models;

/** Partícula del empleado: sistema de pensión (código SP), jubilado y suspensión de cuarta. */
class PlanEmpleadoPension extends PlanParticula
{
    protected $table = 'plan_empleado_pensiones';

    protected $fillable = ['plan_empleado_id', 'desde', 'plan_sp_codigo', 'esta_jubilado', 'suspension_cuarta', 'motivo', 'creado_por', 'actualizado_por'];

    protected $casts = ['desde' => 'date', 'esta_jubilado' => 'boolean', 'suspension_cuarta' => 'boolean'];

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    public function descuento()
    {
        return $this->belongsTo(PlanDescuentoSp::class, 'plan_sp_codigo');
    }
}
