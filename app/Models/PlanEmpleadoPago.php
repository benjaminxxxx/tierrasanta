<?php

namespace App\Models;

/** Partícula del empleado: método de pago y cuentas (principal y la de la diferencia). */
class PlanEmpleadoPago extends PlanParticula
{
    protected $table = 'plan_empleado_pagos';

    protected $fillable = [
        'plan_empleado_id', 'desde', 'metodo_pago', 'banco', 'tipo_cuenta', 'moneda_cuenta', 'numero_cuenta',
        'banco_secundario', 'tipo_cuenta_secundaria', 'moneda_cuenta_secundaria', 'numero_cuenta_secundaria', 'motivo',
        'creado_por', 'actualizado_por',
    ];

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }
}
