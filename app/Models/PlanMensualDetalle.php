<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Empleado dentro de un mes de planilla. Se crea al aperturar el mes y es el ancla de los registros diarios
 * (plan_registros_diarios.plan_det_men_id), por eso no se puede reemplazar ni borrar.
 *
 * Solo se mantienen al día: plan_mensual_id, plan_empleado_id, documento, nombres y orden.
 * El resto de columnas (grupo, spp_snp, montos, negro_*, días, horas, costo_hora…) son HISTÓRICAS: solo tienen
 * datos hasta 01/2026 y no deben leerse. En su lugar:
 * - grupo, sistema de pensión, bonificación, jubilado → contrato vigente del mes (PlanContrato).
 * - días, horas, ingresos, descuentos, aportes, costos → PLAME del mes (PlanMensualPersonal, plan_mensual_personals).
 */
class PlanMensualDetalle extends Model
{
    protected $table = 'plan_mensual_detalles';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        // Relaciones e Identificación
        'plan_mensual_id',
        'plan_empleado_id',
        'documento',
        'nombres',
        'orden',
        'grupo',
        'spp_snp',
        'empleado_grupo_color',
        'esta_jubilado',

        // Conceptos en Blanco (SUNAT/Planilla)
        //'remuneracion_basica',
        'bonificacion',
        'asignacion_familiar',
        'compensacion_vacacional',
        //'sueldo_bruto',
        'dscto_afp_seguro',
        'dscto_afp_seguro_explicacion',
        'sueldo_neto',
        'rem_basica_essalud',
        'rem_basica_asg_fam_essalud_cts_grat_beta',
        'jornal_diario',
        'costo_hora',

        // Conceptos en Negro (Cálculos Internos)
        'negro_sueldo_por_dia_total',
        'negro_sueldo_por_hora_total',
        'negro_otros_bonos_acumulados',
        'negro_sueldo_final_empleado',
        'negro_diferencia_bonificacion',
        'negro_sueldo_neto_total',
        'negro_sueldo_bruto',
        'negro_sueldo_por_dia',
        'negro_sueldo_por_hora',
        'negro_diferencia_por_hora',
        'negro_diferencia_real',

        // Nuevos Insumos y Variables de Tiempo
        'negro_bono_asistencia',
        'negro_bono_productividad',
        'dias_trabajados',
        'horas_trabajadas',
        'horas_trabajadas_reales',
        'blanco_neto_pagar',
        'faltas_injustificadas',

        'dias_laborados',
        'dias_no_laborados',


        //nuevo calculo
        'sueldo_real_proyectado',
        'sueldo_real_liquidado'
    ];
    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }
    public function planillaMensual()
    {
        return $this->belongsTo(PlanMensual::class, 'plan_mensual_id');
    }
    public function registrosDiarios()
    {
        return $this->hasMany(PlanRegistroDiario::class, 'plan_det_men_id');
    }
}
