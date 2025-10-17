<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanRegistroDiario extends Model
{
    use HasFactory;
    protected $table = 'plan_registros_diarios';

    protected $fillable = [
        'plan_det_men_id',
        'asistencia',
        'fecha',
        'total_bono',
        'costo_dia',
        'total_horas',
        'esta_pagado',
        'bono_esta_pagado',
    ];
    public function detalleMensual()
    {
        return $this->belongsTo(PlanMensualDetalle::class, 'plan_det_men_id');
    }

    public function detalles()
    {
        return $this->hasMany(PlanDetalleHora::class,'plan_reg_dia_id');
    }
    public function actividadesBonos()
    {
        return $this->hasMany(PlanActividadBono::class, 'registro_diario_id');
    }
}
