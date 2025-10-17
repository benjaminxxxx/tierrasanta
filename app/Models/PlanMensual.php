<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMensual extends Model
{
    protected $table = 'plan_mensuales';
    
    protected $fillable = [
        'id',
        'mes',
        'anio',
        'dias_laborables',
        'total_horas',
        'factor_remuneracion_basica',
        'total_empleados',
        'excel',
        //campos que se copian a planilla para ya no depender de configuracion
        'asignacion_familiar',
        'cts_porcentaje',
        'gratificaciones',
        'essalud_gratificaciones',
        'rmv',
        'beta30',
        'essalud',
        'vida_ley',
        'vida_ley_porcentaje',
        'pension_sctr',
        'pension_sctr_porcentaje',
        'essalud_eps',
        'porcentaje_constante',
        'rem_basica_essalud'
    ];
   
    public function detalle()
    {
        return $this->hasMany(PlanMensualDetalle::class, 'plan_mensual_id')->orderBy('orden');
    }
}
