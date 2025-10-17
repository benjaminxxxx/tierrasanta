<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanResumenDiarioTipoAsistencia extends Model
{
     use HasFactory;

    protected $table = 'plan_resumen_diario_tipo_asistencias';

    protected $fillable = [
        'plan_res_dia_id',
        'codigo',
        'descripcion',
        'horas_jornal',
        'color',
        'tipo',
        'afecta_sueldo',
        'porcentaje_remunerado',
        'requiere_documento',
        'acumula_asistencia',
        'fecha',
        'total_asistidos',
    ];

    public function resumenDiario()
    {
        return $this->belongsTo(PlanResumenDiario::class, 'plan_res_dia_id');
    }
}
