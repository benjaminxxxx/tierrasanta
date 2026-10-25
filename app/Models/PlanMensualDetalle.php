<?php

namespace App\Models;

use App\Services\Planilla\Mensual\PlanillaMensualReglas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un empleado en un mes de planilla agraria: lista del registro diario + cálculo de la planilla y PLAME del mes
 * (antes separado en plan_mensual_personals). Ver la migración para los bloques de columnas.
 *
 * - La fila nace con la lista del mes (PlanillaListaMensualProceso) y ancla los registros diarios: no se borra ni se
 *   reemplaza mientras tenga registros.
 * - "Generar planilla" llena el cálculo y pone calculado_en; si el empleado no se pudo calcular queda en null. Para
 *   todo lo que es planilla / PLAME / costos usar el scope calculados().
 * - Los valores derivados (proyectado_*, sueldo_pagado, pagado_sueldo_por_hora…) salen de PlanillaMensualReglas.
 */
class PlanMensualDetalle extends Model
{
    protected $table = 'plan_mensual_detalles';

    /** Columnas del cálculo (se vacían cuando el empleado ya no se puede calcular). Valor = vacío. */
    public const CALCULO_VACIO = [
        'calculado_en' => null, 'orden_planilla' => null, 'grupo' => null, 'empleado_grupo_color' => null, 'sistema_pension' => null,
        'es_pensionista' => false, 'edad' => null, 'remuneracion_basica' => null, 'bonificacion' => null, 'asignacion_familiar' => null,
        'compensacion_vacacional' => null, 'proyectado_dscto_afp_prima_seguro' => null, 'proyectado_sueldo_neto_total' => null,
        'sp_01' => 0, 'sp_02' => 0, 'sp_03' => 0, 'sp_04' => 0, 'sp_05' => 0, 'sp_06' => 0, 'sp_07' => 0, 'sp_08' => 0,
        'si_20' => 0, 'si_21' => 0, 'si_22' => 0, 'si_23' => 0, 'si_24' => 0, 'si_25' => 0, 'si_26' => 0, 'si_27' => 0,
        'plame_dias_no_laborados' => 0, 'plame_dias_laborados' => 0, 'plame_total_horas' => 0, 'plame_horas_jornada' => null,
        'plame_0117_comp_vacacional' => 0, 'plame_0118_rem_vacacional' => 0, 'plame_0121_rem_jornal_basico' => 0,
        'plame_0201_asignacion_familiar' => 0, 'plame_remuneracion_bruta' => 0, 'plame_0312_bonif_ext_temp' => 0,
        'plame_0314_beta_30' => 0, 'plame_0406_gratif_fiestas_navidad' => 0, 'plame_0904_cts' => 0,
        'plame_descuento_0601_comision_afp_pct' => 0, 'plame_descuento_0605_renta_5ta_retenida' => 0,
        'plame_descuento_0606_prima_seguro_afp' => 0, 'plame_descuento_0607_snp' => 0, 'plame_descuento_0608_spp_aporte_obligatorio' => 0,
        'plame_neto_a_pagar' => 0, 'plame_aporte_empleador_0803_poliza' => 0, 'plame_aporte_empleador_0804_essalud' => 0,
        'plame_aporte_empleador_0805_sctr' => 0, 'plame_aporte_empleador_0810_eps' => 0, 'plame_ajustes' => null, 'plame_calculados' => null,
        'bonificacion_asistencia' => null, 'vacaciones_plame_personalizado' => null, 'vacaciones_neto_pagadas' => null, 'vacaciones_negro' => null,
    ];

    /** Parámetros del mes (plan_mensuales) que usan las fórmulas */
    private const PARAMETROS_MES = ['cts', 'gratificaciones', 'essalud_gratificaciones', 'rmv', 'beta30', 'essalud', 'vida_ley',
        'pension_sctr', 'essalud_eps', 'dias_laborables', 'total_horas'];

    protected $fillable = [
        // 1. Lista
        'plan_mensual_id', 'plan_empleado_id', 'orden', 'documento', 'nombres',
        // 2. Datos del mes y proyectado
        'calculado_en', 'orden_planilla', 'grupo', 'empleado_grupo_color', 'sistema_pension', 'es_pensionista', 'edad',
        'remuneracion_basica', 'bonificacion', 'asignacion_familiar', 'compensacion_vacacional',
        'proyectado_dscto_afp_prima_seguro', 'proyectado_sueldo_neto_total',
        // 3. Suspensiones
        'sp_01', 'sp_02', 'sp_03', 'sp_04', 'sp_05', 'sp_06', 'sp_07', 'sp_08',
        'si_20', 'si_21', 'si_22', 'si_23', 'si_24', 'si_25', 'si_26', 'si_27',
        // 4. PLAME
        'plame_dias_no_laborados', 'plame_dias_laborados', 'plame_total_horas', 'plame_horas_jornada',
        'plame_0117_comp_vacacional', 'plame_0118_rem_vacacional', 'plame_0121_rem_jornal_basico', 'plame_0201_asignacion_familiar',
        'plame_remuneracion_bruta', 'plame_0312_bonif_ext_temp', 'plame_0314_beta_30', 'plame_0406_gratif_fiestas_navidad', 'plame_0904_cts',
        'plame_descuento_0601_comision_afp_pct', 'plame_descuento_0605_renta_5ta_retenida', 'plame_descuento_0606_prima_seguro_afp',
        'plame_descuento_0607_snp', 'plame_descuento_0608_spp_aporte_obligatorio',
        'plame_aporte_empleador_0803_poliza', 'plame_aporte_empleador_0804_essalud', 'plame_aporte_empleador_0805_sctr',
        'plame_aporte_empleador_0810_eps', 'plame_neto_a_pagar', 'plame_ajustes', 'plame_calculados',
        // 5. Pagos reales
        'bonificacion_asistencia', 'vacaciones_plame_personalizado', 'vacaciones_neto_pagadas', 'vacaciones_negro',
    ];

    protected function casts(): array
    {
        $enteros = array_fill_keys(['orden', 'orden_planilla', 'plan_empleado_id', 'edad', 'plame_dias_no_laborados', 'plame_dias_laborados',
            'sp_01', 'sp_02', 'sp_03', 'sp_04', 'sp_05', 'sp_06', 'sp_07', 'sp_08',
            'si_20', 'si_21', 'si_22', 'si_23', 'si_24', 'si_25', 'si_26', 'si_27'], 'integer');
        $decimales = array_fill_keys(['plame_total_horas', 'plame_horas_jornada', 'plame_0117_comp_vacacional', 'plame_0118_rem_vacacional',
            'plame_0121_rem_jornal_basico', 'plame_0201_asignacion_familiar', 'plame_remuneracion_bruta', 'plame_0312_bonif_ext_temp',
            'plame_0314_beta_30', 'plame_0406_gratif_fiestas_navidad', 'plame_0904_cts', 'plame_descuento_0601_comision_afp_pct',
            'plame_descuento_0605_renta_5ta_retenida', 'plame_descuento_0606_prima_seguro_afp', 'plame_descuento_0607_snp',
            'plame_descuento_0608_spp_aporte_obligatorio', 'plame_neto_a_pagar', 'plame_aporte_empleador_0803_poliza',
            'plame_aporte_empleador_0804_essalud', 'plame_aporte_empleador_0805_sctr', 'plame_aporte_empleador_0810_eps'], 'decimal:2');
        return $enteros + $decimales + [
            'es_pensionista' => 'boolean',
            'calculado_en' => 'datetime',
            'plame_ajustes' => 'array',
            'plame_calculados' => 'array',
        ];
    }

    protected $appends = [
        'proyectado_cts', 'proyectado_gratificaciones', 'proyectado_essalud_gratificaciones', 'proyectado_beta30',
        'proyeccion_sueldo_bruto', 'proyectado_essalud', 'proyectado_vida_ley', 'proyectado_pension_sctr', 'proyectado_essalud_eps',
        'proyectado_sueldo_neto', 'proyectado_sueldo_neto_beneficios', 'proyectado_sueldo_bruto_beneficios_aportes',
        'proyectado_jornal_diario', 'proyectado_costo_hora', 'proyectado_diferencia_bonificacion', 'proyectado_sueldo_bruto_negro',
        'proyectado_sueldo_por_dia', 'proyectado_sueldo_por_hora', 'pagado_sueldo_bruto_negro',
    ];

    // ------------------------------------------------------------------ relaciones y scopes

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    /** Mismo que empleado() (nombre que usan la planilla y el PLAME) */
    public function planEmpleado(): BelongsTo
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }

    public function planillaMensual(): BelongsTo
    {
        return $this->belongsTo(PlanMensual::class, 'plan_mensual_id');
    }

    /** Mismo que planillaMensual() */
    public function planMensual(): BelongsTo
    {
        return $this->belongsTo(PlanMensual::class, 'plan_mensual_id');
    }

    public function registrosDiarios(): HasMany
    {
        return $this->hasMany(PlanRegistroDiario::class, 'plan_det_men_id');
    }

    /** Solo las filas con planilla calculada (las que van en la planilla, el PLAME y los costos) */
    public function scopeCalculados(Builder $q): Builder
    {
        return $q->whereNotNull('calculado_en');
    }

    public function scopeDelMes(Builder $q, int $mes, int $anio): Builder
    {
        return $q->whereHas('planMensual', fn($p) => $p->where('mes', $mes)->where('anio', $anio));
    }

    // ------------------------------------------------------------------ PLAME

    /**
     * Ajustes manuales del PLAME: las columnas plame_* guardan el valor final; si un concepto se ajustó a mano,
     * plame_ajustes tiene su monto y motivo, y plame_calculados lo que habría calculado el sistema.
     *
     * @return array{monto: float, motivo: ?string}|null
     */
    public function ajustePlame(string $codigo): ?array
    {
        return $this->plame_ajustes[$codigo] ?? null;
    }

    /** Lo que calcula el sistema para el concepto (sin el ajuste). */
    public function calculadoPlame(string $codigo): ?float
    {
        $valor = $this->plame_calculados[$codigo] ?? null;
        return $valor === null ? null : (float) $valor;
    }

    /** Bonos de productividad del mes (suma de sus registros diarios). Se puede precargar como atributo bono_productividad. */
    public function getBonoProductividadAttribute(): float
    {
        if (array_key_exists('bono_productividad', $this->attributes)) {
            return (float) $this->attributes['bono_productividad'];
        }
        return $this->id ? (float) $this->registrosDiarios()->sum('total_bono') : 0.0;
    }

    // ------------------------------------------------------------------ valores derivados (PlanillaMensualReglas)

    private function valores(array $columnas): array
    {
        $r = [];
        foreach ($columnas as $c) {
            $r[$c] = $this->getAttribute($c);
        }
        return $r;
    }

    private function parametrosMes(): array
    {
        $mes = $this->planMensual;
        $r = [];
        foreach (self::PARAMETROS_MES as $c) {
            $r[$c] = $mes?->getAttribute($c);
        }
        return $r;
    }

    private function proyeccion(): array
    {
        return PlanillaMensualReglas::proyeccion($this->valores(['remuneracion_basica', 'bonificacion', 'asignacion_familiar',
            'compensacion_vacacional', 'proyectado_dscto_afp_prima_seguro', 'proyectado_sueldo_neto_total']), $this->parametrosMes());
    }

    private function pagado(): array
    {
        return PlanillaMensualReglas::pagado($this->valores(array_merge(['proyectado_sueldo_neto_total', 'plame_total_horas'],
            PlanillaMensualReglas::DESCUENTOS_TRABAJADOR, PlanillaMensualReglas::APORTES_EMPLEADOR)), $this->parametrosMes());
    }

    private function derivado(string $grupo, string $clave): Attribute
    {
        return Attribute::get(fn() => $this->{$grupo}()[$clave]);
    }

    protected function proyeccionSueldoBruto(): Attribute { return $this->derivado('proyeccion', 'proyeccion_sueldo_bruto'); }
    protected function proyectadoCts(): Attribute { return $this->derivado('proyeccion', 'proyectado_cts'); }
    protected function proyectadoGratificaciones(): Attribute { return $this->derivado('proyeccion', 'proyectado_gratificaciones'); }
    protected function proyectadoEssaludGratificaciones(): Attribute { return $this->derivado('proyeccion', 'proyectado_essalud_gratificaciones'); }
    protected function proyectadoBeta30(): Attribute { return $this->derivado('proyeccion', 'proyectado_beta30'); }
    protected function proyectadoEssalud(): Attribute { return $this->derivado('proyeccion', 'proyectado_essalud'); }
    protected function proyectadoVidaLey(): Attribute { return $this->derivado('proyeccion', 'proyectado_vida_ley'); }
    protected function proyectadoPensionSctr(): Attribute { return $this->derivado('proyeccion', 'proyectado_pension_sctr'); }
    protected function proyectadoEssaludEps(): Attribute { return $this->derivado('proyeccion', 'proyectado_essalud_eps'); }
    protected function proyectadoSueldoNeto(): Attribute { return $this->derivado('proyeccion', 'proyectado_sueldo_neto'); }
    protected function proyectadoSueldoNetoBeneficios(): Attribute { return $this->derivado('proyeccion', 'proyectado_sueldo_neto_beneficios'); }
    protected function proyectadoSueldoBrutoBeneficiosAportes(): Attribute { return $this->derivado('proyeccion', 'proyectado_sueldo_bruto_beneficios_aportes'); }
    protected function proyectadoJornalDiario(): Attribute { return $this->derivado('proyeccion', 'proyectado_jornal_diario'); }
    protected function proyectadoCostoHora(): Attribute { return $this->derivado('proyeccion', 'proyectado_costo_hora'); }
    protected function proyectadoDiferenciaBonificacion(): Attribute { return $this->derivado('proyeccion', 'proyectado_diferencia_bonificacion'); }
    protected function proyectadoSueldoBrutoNegro(): Attribute { return $this->derivado('proyeccion', 'proyectado_sueldo_bruto_negro'); }
    protected function proyectadoSueldoPorDia(): Attribute { return $this->derivado('proyeccion', 'proyectado_sueldo_por_dia'); }
    protected function proyectadoSueldoPorHora(): Attribute { return $this->derivado('proyeccion', 'proyectado_sueldo_por_hora'); }

    protected function sueldoPagado(): Attribute { return $this->derivado('pagado', 'sueldo_pagado'); }
    protected function aportesTrabajador(): Attribute { return $this->derivado('pagado', 'aportes_trabajador'); }
    protected function aportesEmpleador(): Attribute { return $this->derivado('pagado', 'aportes_empleador'); }
    protected function pagadoSueldoBrutoNegro(): Attribute { return $this->derivado('pagado', 'pagado_sueldo_bruto_negro'); }
    protected function pagadoSueldoPorHora(): Attribute { return $this->derivado('pagado', 'pagado_sueldo_por_hora'); }
    protected function pagoJornalDiario(): Attribute { return $this->derivado('pagado', 'pago_jornal_diario'); }
}
