<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanContrato extends Model
{
    protected $fillable = [
        'plan_empleado_id',
        'tipo_contrato',
        'fecha_inicio',
        'fecha_fin',
        'sueldo',
        'cargo_codigo',
        'grupo_codigo',
        'compensacion_vacacional',
        'tipo_planilla',
        'plan_sp_codigo',
        'esta_jubilado',
        'modalidad_pago',
        'motivo_despido',
    ];
    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class);
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
}
