<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanillaBlancoDetalle extends Model
{
    protected $table = 'planilla_blanco_detalles';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'documento',
        'nombres',
        'spp_snp',
        'orden',
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
        'negro_sueldo_por_hora',
        'negro_diferencia_por_hora',
        'negro_diferencia_real',

        'planilla_blanco_id',
    ];

    /**
     * Relación con PlanillaBlanco
     * Cada detalle pertenece a una planilla en blanco.
     */
    public function planillaBlanco()
    {
        return $this->belongsTo(PlanillaBlanco::class);
    }
}
