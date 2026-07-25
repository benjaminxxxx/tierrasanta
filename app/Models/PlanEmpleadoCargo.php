<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEmpleadoCargo extends Model
{
    protected $fillable = [
        'plan_empleado_id',
        'plan_cargo_id',
        'grupo_codigo',
        'fecha_inicio',
        'fecha_fin',
        'motivo_cambio',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    public function cargo()
    {
        return $this->belongsTo(PlanCargo::class, 'plan_cargo_id');
    }
}
