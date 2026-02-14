<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

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
        'blanco_neto_pagar',
        'faltas_injustificadas'
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
        'remuneracion_basica',
        'blanco_descuento_por_faltas',
        'blanco_remuneracion_bruta',
        'blanco_descuento_onp_afp_prima',
        'blanco_beta30',
        'blanco_cts',
        'blanco_gratificaciones',
        'blanco_essalud_gratificaciones',
        //GASTOS DEL EMPLEADOR
        'blanco_essalud',
        'blanco_vida_ley',
        'blanco_pension_sctr',
        'blanco_essalud_eps',
        //TOTALES BLANCOA
        'blanco_sueldo_neto',

        'sueldo_negro_subtotal',
        'sueldo_negro_total'
    ];
    public function getRemuneracionBasicaAttribute()
    {
        $plan = $this->planillaMensual;
        $fecha = Carbon::createFromDate($plan->anio, $plan->mes, 1);
        $diasDelMes = $fecha->daysInMonth;

        // Mantenemos precisión total
        return ($plan->rmv / 30) * $diasDelMes;
    }
    public function getBlancoDescuentoPorFaltasAttribute()
    {
        return ($this->remuneracion_basica / 30) * $this->faltas_injustificadas;
    }
    /*
    public function getBlancoDescuentoPorFaltasAttribute()
        {
            if ($this->faltas_injustificadas <= 0) return 0;

            // Valor día + Proporcional dominical (treintavo)
            $valorDia = $this->remuneracion_basica / 30;
            $proporcionalDescanso = $valorDia / 30;

            return ($valorDia + $proporcionalDescanso) * $this->faltas_injustificadas;
        } */
    public function getBlancoRemuneracionBrutaAttribute()
    {
        return $this->remuneracion_basica + ($this->asignacion_familiar ?? 0) - $this->blanco_descuento_por_faltas;
    }
    public function getBlancoDescuentoOnpAfpPrimaAttribute()
    {
        return ($this->dscto_afp_seguro / 100) * $this->blanco_remuneracion_bruta;
    }
    public function getBlancoBeta30Attribute()
    {
        $plan = $this->planillaMensual;
        return $plan->beta30 - (($plan->beta30 / 30) * $this->faltas_injustificadas);
    }
    public function getBlancoCtsAttribute()
    {
        $plan = $this->planillaMensual;
        return ($plan->cts_porcentaje / 100) * ($this->remuneracion_basica + $this->asignacion_familiar);
    }
    public function getBlancoGratificacionesAttribute()
    {
        $plan = $this->planillaMensual;
        return ($plan->gratificaciones / 100) * ($this->remuneracion_basica + $this->asignacion_familiar);
    }
    public function getBlancoEssaludGratificacionesAttribute()
    {
        $plan = $this->planillaMensual;
        return ($plan->essalud_gratificaciones / 100) * $this->blanco_gratificaciones;
    }
    // --- GASTOS DEL EMPLEADOR ---

    public function getBlancoEssaludAttribute()
    {
        return ($this->planillaMensual->essalud / 100) * $this->blanco_remuneracion_bruta;
    }

    public function getBlancoVidaLeyAttribute()
    {
        return (($this->planillaMensual->vida_ley / 100) * $this->blanco_remuneracion_bruta) * 1.18;
    }

    public function getBlancoPensionSctrAttribute()
    {
        return (($this->planillaMensual->pension_sctr / 100) * $this->blanco_remuneracion_bruta) * 1.18;
    }

    public function getBlancoEssaludEpsAttribute()
    {
        return (($this->planillaMensual->essalud_eps / 100) * $this->blanco_remuneracion_bruta) * 1.18;
    }
    public function getBlancoSueldoNetoAttribute()
    {
        return (($this->blanco_remuneracion_bruta - $this->blanco_descuento_onp_afp_prima)
            + $this->blanco_cts
            + $this->blanco_beta30
            + $this->blanco_gratificaciones
            + $this->blanco_essalud_gratificaciones);
    }
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
