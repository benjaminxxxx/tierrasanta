<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'blanco_neto_pagar',
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
    protected $appends = [
        'sueldo_negro_subtotal',
        'sueldo_negro_total'
    ];
    /**
     * Sueldo mensual proporcional según horas trabajadas.
     */
    protected function sueldoNegroSubtotal(): Attribute
    {
        return Attribute::make(
            get: function () {

                $horasTrabajadas = (float) $this->horas_trabajadas;
                $sueldoBase = (float) $this->negro_sueldo_bruto;

                // Validar relación
                if (!$this->relationLoaded('planillaMensual')) {
                    $this->load('planillaMensual');
                }

                $horasMes = (float) ($this->planillaMensual->total_horas ?? 0);

                if ($horasMes <= 0) {
                    return 0; // Evita división por cero
                }

                return round($sueldoBase * ($horasTrabajadas / $horasMes), 2);
            }
        );
    }
    protected function sueldoNegroTotal(): Attribute
    {
        return Attribute::make(
            get: function () {
                return
                    floatval($this->sueldo_negro_subtotal) +
                    floatval($this->negro_bono_asistencia) +
                    floatval($this->negro_bono_productividad);
            }
        );
    }
}
