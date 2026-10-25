<?php

namespace App\Models;

/** Partícula del contrato: cargo y grupo. */
class PlanContratoAsignacion extends PlanParticula
{
    protected $table = 'plan_contrato_asignaciones';

    protected $fillable = ['plan_contrato_id', 'desde', 'plan_cargo_id', 'grupo_codigo', 'motivo', 'creado_por', 'actualizado_por'];

    public function contrato()
    {
        return $this->belongsTo(PlanContrato::class, 'plan_contrato_id');
    }

    public function cargo()
    {
        return $this->belongsTo(PlanCargo::class, 'plan_cargo_id');
    }

    public function grupo()
    {
        return $this->belongsTo(PlanGrupo::class, 'grupo_codigo');
    }
}
