<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanillaBlanco extends Model
{
    protected $table = 'planillas_blanco';
    
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
        return $this->hasMany(PlanillaBlancoDetalle::class, 'planilla_blanco_id')->orderBy('orden');
    }
   
}
