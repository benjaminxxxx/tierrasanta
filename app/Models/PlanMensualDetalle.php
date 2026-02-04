<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMensualDetalle extends Model
{
    protected $table = 'plan_mensual_detalles';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        
        'plan_mensual_id',
        'plan_empleado_id',
        'documento',
        'nombres',
        'spp_snp',
        'orden',
        'grupo',
        'empleado_grupo_color',
        'remuneracion_basica',
        'bonificacion',
        'asignacion_familiar',
        'compensacion_vacacional',
        'sueldo_bruto',
        'dscto_afp_seguro',
        'dscto_afp_seguro_explicacion',
        'cts',
        'gratificaciones',
        'essalud_gratificaciones',
        'beta_30',
        'essalud',
        'vida_ley',
        'pension_sctr',
        'essalud_eps',
        'sueldo_neto',
        'rem_basica_essalud',
        'rem_basica_asg_fam_essalud_cts_grat_beta',
        'jornal_diario',
        'costo_hora',

        // Nuevos campos con prefijo 'negro_'
        'negro_diferencia_bonificacion',
        'negro_sueldo_neto_total',
        'negro_sueldo_bruto',
        'negro_sueldo_por_dia',
        'negro_sueldo_por_dia_total',
        'negro_sueldo_por_hora',
        'negro_sueldo_por_hora_total',
        'negro_diferencia_por_hora',
        'negro_otros_bonos_acumulados',
        'negro_sueldo_final_empleado',
        'negro_diferencia_real',
        'esta_jubilado',

        'sueldo_negro_pagado',
        'sueldo_blanco_pagado',
        'total_horas'
    ];
    public function empleado(){
        return $this->belongsTo(PlanEmpleado::class,'plan_empleado_id');
    }
    public function planillaMensual()
    {
        return $this->belongsTo(PlanMensual::class,'plan_mensual_id');
    }
    public function registrosDiarios(){
        return $this->hasMany(PlanRegistroDiario::class,'plan_det_men_id');
    }
}
