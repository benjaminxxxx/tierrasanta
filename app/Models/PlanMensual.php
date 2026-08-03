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
        'remuneracion_basica',
        //'factor_remuneracion_basica',
        'total_empleados',
        'excel',
        //campos que se copian a planilla para ya no depender de configuracion
        'asignacion_familiar',
        'gratificaciones',
        'essalud_gratificaciones',
        'rmv',
        'beta30',
        'essalud',
        'vida_ley',
        'pension_sctr',
        'essalud_eps',
        'rem_basica_essalud',
        'cts'
    ];
   
    public function detalle()
    {
        return $this->hasMany(PlanMensualDetalle::class, 'plan_mensual_id')->orderBy('orden');
    }
    public function planilla()
    {
        return $this->hasMany(PlanMensualPersonal::class, 'plan_mensual_id')->orderBy('orden');
    }
}
