<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanMensualPersonal extends Model
{
    public const FACTOR_SEGURO = 1.18;
    protected $table = 'plan_mensual_personals';

    protected $fillable = [
        'nombres',
        'orden',
        'grupo',
        'plan_mensual_id',
        'plan_empleado_id',
        'empleado_grupo_color',
        'sistema_pension',
        'es_pensionista',
        'edad',

        // Proyectado
        'remuneracion_basica',
        'bonificacion',
        'asignacion_familiar',
        'compensacion_vacacional',
        'proyectado_dscto_afp_prima_seguro',
        'proyectado_sueldo_neto_total', //acuerdo con el empleado si no falta
        //'proyectado_sueldo_bruto_negro',

        // PLAME - Suspensión perfecta
        'sp_01',
        'sp_02',
        'sp_03',
        'sp_04',
        'sp_05',
        'sp_06',
        'sp_07',
        'sp_08',

        // PLAME - Suspensión imperfecta
        'si_20',
        'si_21',
        'si_22',
        'si_23',
        'si_24',
        'si_25',
        'si_26',
        'si_27',

        // PLAME - Días y horas
        'plame_dias_no_laborados',
        'plame_dias_laborados',
        'plame_total_horas',

        // PLAME - Ingresos
        'plame_0117_comp_vacacional',
        'plame_0118_rem_vacacional',
        'plame_0121_rem_jornal_basico',
        'plame_0201_asignacion_familiar',
        'plame_remuneracion_bruta',
        'plame_0312_bonif_ext_temp',
        'plame_0314_beta_30',
        'plame_0406_gratif_fiestas_navidad',
        'plame_0904_cts',

        // PLAME - Descuentos
        'plame_descuento_0601_comision_afp_pct',
        'plame_descuento_0605_renta_5ta_retenida',
        'plame_descuento_0606_prima_seguro_afp',
        'plame_descuento_0607_snp',
        'plame_descuento_0608_spp_aporte_obligatorio',

        // PLAME - Neto
        'plame_neto_a_pagar',

        // PLAME - Aportes del empleador
        'plame_aporte_empleador_0803_poliza',
        'plame_aporte_empleador_0804_essalud',
        'plame_aporte_empleador_0805_sctr',
        'plame_aporte_empleador_0810_eps',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'plan_empleado_id' => 'integer',
            'es_pensionista' => 'boolean',
            'edad' => 'integer',

            // Suspensiones
            'sp_01' => 'integer',
            'sp_02' => 'integer',
            'sp_03' => 'integer',
            'sp_04' => 'integer',
            'sp_05' => 'integer',
            'sp_06' => 'integer',
            'sp_07' => 'integer',
            'sp_08' => 'integer',

            'si_20' => 'integer',
            'si_21' => 'integer',
            'si_22' => 'integer',
            'si_23' => 'integer',
            'si_24' => 'integer',
            'si_25' => 'integer',
            'si_26' => 'integer',
            'si_27' => 'integer',

            // Días y horas
            'plame_dias_no_laborados' => 'integer',
            'plame_dias_laborados' => 'integer',
            'plame_total_horas' => 'decimal:2',

            // Ingresos
            'plame_0117_comp_vacacional' => 'decimal:2',
            'plame_0118_rem_vacacional' => 'decimal:2',
            'plame_0121_rem_jornal_basico' => 'decimal:2',
            'plame_0201_asignacion_familiar' => 'decimal:2',
            'plame_remuneracion_bruta' => 'decimal:2',
            'plame_0312_bonif_ext_temp' => 'decimal:2',
            'plame_0314_beta_30' => 'decimal:2',
            'plame_0406_gratif_fiestas_navidad' => 'decimal:2',
            'plame_0904_cts' => 'decimal:2',

            // Descuentos
            'plame_descuento_0601_comision_afp_pct' => 'decimal:2',
            'plame_descuento_0605_renta_5ta_retenida' => 'decimal:2',
            'plame_descuento_0606_prima_seguro_afp' => 'decimal:2',
            'plame_descuento_0607_snp' => 'decimal:2',
            'plame_descuento_0608_spp_aporte_obligatorio' => 'decimal:2',

            // Neto
            'plame_neto_a_pagar' => 'decimal:2',

            // Aportes empleador
            'plame_aporte_empleador_0803_poliza' => 'decimal:2',
            'plame_aporte_empleador_0804_essalud' => 'decimal:2',
            'plame_aporte_empleador_0805_sctr' => 'decimal:2',
            'plame_aporte_empleador_0810_eps' => 'decimal:2',
        ];
    }

    public function planEmpleado(): BelongsTo
    {
        return $this->belongsTo(PlanEmpleado::class);
    }
    public function planMensual(): BelongsTo
    {
        return $this->belongsTo(PlanMensual::class);
    }
    /**
     * Factor fijo aplicado a vida ley, pensión SCTR y EsSalud EPS.
     * No es configurable por periodo; ajusta aquí si cambia legalmente.
     */

    protected $appends = [
        'proyectado_cts',
        'proyectado_gratificaciones',
        'proyectado_essalud_gratificaciones',
        'proyectado_beta30',
        'proyeccion_sueldo_bruto',
        'proyectado_essalud',
        'proyectado_vida_ley',
        'proyectado_pension_sctr',
        'proyectado_essalud_eps',
        'proyectado_sueldo_neto',
        'proyectado_sueldo_neto_beneficios',
        'proyectado_sueldo_bruto_beneficios_aportes',
        'proyectado_jornal_diario',
        'proyectado_costo_hora',
        'proyectado_diferencia_bonificacion',
        'proyectado_sueldo_bruto_negro',
        'proyectado_sueldo_por_dia',
        'proyectado_sueldo_por_hora',
    ];

    /**
     * D7 + E7 + F7: remuneración básica + bonificación + asignación familiar.
     * Deliberadamente excluye compensación vacacional.
     */
    protected function sueldoBrutoSinCompensacion(): float
    {
        return (float) $this->remuneracion_basica
            + (float) $this->bonificacion
            + (float) $this->asignacion_familiar;
    }

    /**
     * =((D7+E7+F7)*(9.72%))
     */
    protected function proyectadoCts(): Attribute
    {
        return Attribute::get(function () {
            $cts = $this->planMensual?->cts;

            if (is_null($cts)) {
                return null;
            }

            return round($this->sueldoBrutoSinCompensacion() * ($cts / 100), 2);
        });
    }

    /**
     * =(D7+E7+F7)*(16.66%)
     * El porcentaje viene de plan_mensuales.gratificaciones (configurado por periodo).
     */
    protected function proyectadoGratificaciones(): Attribute
    {
        return Attribute::get(function () {
            $porcentaje = $this->planMensual?->gratificaciones;

            if (is_null($porcentaje)) {
                return null;
            }

            return round($this->sueldoBrutoSinCompensacion() * ($porcentaje / 100), 2);
        });
    }

    /**
     * =L7*6% (L7 = gratificaciones ya calculada arriba)
     * El porcentaje viene de plan_mensuales.essalud_gratificaciones.
     */
    protected function proyectadoEssaludGratificaciones(): Attribute
    {
        return Attribute::get(function () {
            $porcentaje = $this->planMensual?->essalud_gratificaciones;
            $gratificaciones = $this->proyectado_gratificaciones;

            if (is_null($porcentaje) || is_null($gratificaciones)) {
                return null;
            }

            return round($gratificaciones * ($porcentaje / 100), 2);
        });
    }

    /**
     * =(1130*30%)/(30)*30 => equivalente a RMV * 30%
     * rmv y el porcentaje (beta30) vienen de plan_mensuales.
     */
    protected function proyectadoBeta30(): Attribute
    {
        return Attribute::get(function () {
            $rmv = $this->planMensual?->rmv;
            $porcentaje = $this->planMensual?->beta30;

            if (is_null($rmv) || is_null($porcentaje)) {
                return null;
            }

            return round($rmv * ($porcentaje / 100), 2);
        });
    }

    /**
     * H7 = remuneración básica + bonificación + asignación familiar + compensación vacacional
     */
    protected function proyeccionSueldoBruto(): Attribute
    {
        return Attribute::get(
            fn() => (float) $this->remuneracion_basica
            + (float) $this->bonificacion
            + (float) $this->asignacion_familiar
            + (float) $this->compensacion_vacacional
        );
    }

    /**
     * =H7*6%
     */
    protected function proyectadoEssalud(): Attribute
    {
        return Attribute::get(function () {
            $porcentaje = $this->planMensual?->essalud;

            if (is_null($porcentaje)) {
                return null;
            }

            return round($this->proyeccion_sueldo_bruto * ($porcentaje / 100), 2);
        });
    }

    /**
     * =((H7*(0.63%))*1.18)
     * proyectado_vida_ley
     */
    protected function proyectadoVidaLey(): Attribute
    {
        return Attribute::get(function () {
            $porcentaje = $this->planMensual?->vida_ley;

            if (is_null($porcentaje)) {
                return null;
            }

            return round($this->proyeccion_sueldo_bruto * ($porcentaje / 100) * self::FACTOR_SEGURO, 2);
        });
    }

    /**
     * =(H7*(0.57%))*1.18
     */
    protected function proyectadoPensionSctr(): Attribute
    {
        return Attribute::get(function () {
            $porcentaje = $this->planMensual?->pension_sctr;

            if (is_null($porcentaje)) {
                return null;
            }

            return round($this->proyeccion_sueldo_bruto * ($porcentaje / 100) * self::FACTOR_SEGURO, 2);
        });
    }

    /**
     * =(H7*(0.55%))*1.18
     */
    protected function proyectadoEssaludEps(): Attribute
    {
        return Attribute::get(function () {
            $porcentaje = $this->planMensual?->essalud_eps;

            if (is_null($porcentaje)) {
                return null;
            }

            return round($this->proyeccion_sueldo_bruto * ($porcentaje / 100) * self::FACTOR_SEGURO, 2);
        });
    }



    /**
     * J7 = H7 - I7 = sueldo bruto - dscto AFP/prima seguro
     */
    protected function proyectadoSueldoNeto(): Attribute
    {
        return Attribute::get(
            fn() => round(
                $this->proyeccion_sueldo_bruto - (float) $this->proyectado_dscto_afp_prima_seguro,
                2
            )
        );
    }

    /**
     * =J7+K7+L7+M7+N7
     * SUELDO NETO + BENF. SOCI. PROYEC.
     */
    protected function proyectadoSueldoNetoBeneficios(): Attribute
    {
        return Attribute::get(function () {
            $valores = [
                $this->proyectado_sueldo_neto,
                $this->proyectado_cts,
                $this->proyectado_gratificaciones,
                $this->proyectado_essalud_gratificaciones,
                $this->proyectado_beta30,
            ];

            if (in_array(null, $valores, true)) {
                return null;
            }

            return round(array_sum($valores), 2);
        });
    }

    /**
     * =H7+K7+L7+M7+N7+O7+P7+Q7+R7
     */
    protected function proyectadoSueldoBrutoBeneficiosAportes(): Attribute
    {
        return Attribute::get(function () {
            $valores = [
                $this->proyeccion_sueldo_bruto,
                $this->proyectado_cts,
                $this->proyectado_gratificaciones,
                $this->proyectado_essalud_gratificaciones,
                $this->proyectado_beta30,
                $this->proyectado_essalud,
                $this->proyectado_vida_ley,
                $this->proyectado_pension_sctr,
                $this->proyectado_essalud_eps,
            ];

            if (in_array(null, $valores, true)) {
                return null;
            }

            return round(array_sum($valores), 2);
        });
    }

    /**
     * =T7/$R$2  (T7 = sueldo bruto + beneficios + aportes; $R$2 = días laborables del periodo)
     */
    protected function proyectadoJornalDiario(): Attribute
    {
        return Attribute::get(function () {
            $total = $this->proyectado_sueldo_bruto_beneficios_aportes;
            $diasLaborables = $this->planMensual?->dias_laborables;

            if (is_null($total) || empty($diasLaborables)) {
                return null;
            }

            return round($total / $diasLaborables, 2);
        });
    }

    /**
     * =U7/8
     */
    protected function proyectadoCostoHora(): Attribute
    {
        return Attribute::get(function () {
            $jornal = $this->proyectado_jornal_diario;

            if (is_null($jornal)) {
                return null;
            }

            return round($jornal / 8, 2);
        });
    }

    /**
     * =AA7-S7
     * AA7 = proyectado_sueldo_neto_total (columna guardada, lo que realmente recibe el trabajador)
     * S7  = proyectado_sueldo_neto_beneficios (calculado, lo que le correspondería formalmente)
     */
    protected function proyectadoDiferenciaBonificacion(): Attribute
    {
        return Attribute::get(function () {
            $netoTotal = $this->proyectado_sueldo_neto_total;
            $netoBeneficios = $this->proyectado_sueldo_neto_beneficios;

            if (is_null($netoTotal) || is_null($netoBeneficios)) {
                return null;
            }

            return round($netoTotal - $netoBeneficios, 2);
        });
    }

    /**
     * =T7+Z7
     * T7 = proyectado_sueldo_bruto_beneficios_aportes
     * Z7 = proyectado_diferencia_bonificacion
     */
    protected function proyectadoSueldoBrutoNegro(): Attribute
    {
        return Attribute::get(function () {
            $bruto = $this->proyectado_sueldo_bruto_beneficios_aportes;
            $diferencia = $this->proyectado_diferencia_bonificacion;

            if (is_null($bruto) || is_null($diferencia)) {
                return null;
            }

            return round($bruto + $diferencia, 2);
        });
    }

    /**
     * =AB7/$R$2
     */
    protected function proyectadoSueldoPorDia(): Attribute
    {
        return Attribute::get(function () {
            $total = $this->proyectado_sueldo_bruto_negro;
            $diasLaborables = $this->planMensual?->dias_laborables;

            if (is_null($total) || empty($diasLaborables)) {
                return null;
            }

            return round($total / $diasLaborables, 2);
        });
    }

    /**
     * =+AC7/8
     */
    protected function proyectadoSueldoPorHora(): Attribute
    {
        return Attribute::get(function () {
            $porDia = $this->proyectado_sueldo_por_dia;

            if (is_null($porDia)) {
                return null;
            }

            return round($porDia / 8, 5);
        });
    }
}