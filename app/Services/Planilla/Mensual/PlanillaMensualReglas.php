<?php

namespace App\Services\Planilla\Mensual;

/**
 * Fórmulas de la planilla agraria del mes (sin base de datos). Se calculan a partir de la fila del empleado en el mes
 * (plan_mensual_detalles) y de los parámetros del mes (plan_mensuales). El modelo PlanMensualDetalle las expone como
 * atributos (proyectado_cts, sueldo_pagado…), así que si cambia una regla se cambia solo aquí.
 *
 * Entradas de la fila: remuneracion_basica, bonificacion, asignacion_familiar, compensacion_vacacional,
 * proyectado_dscto_afp_prima_seguro, proyectado_sueldo_neto_total, plame_total_horas y los plame_descuento_* /
 * plame_aporte_empleador_*.
 * Parámetros del mes: cts, gratificaciones, essalud_gratificaciones, rmv, beta30, essalud, vida_ley, pension_sctr,
 * essalud_eps, dias_laborables, total_horas (null si el mes no los tiene).
 */
class PlanillaMensualReglas
{
    /** Factor fijo de vida ley, pensión SCTR y EsSalud EPS (no es configurable por periodo; cambia por ley). */
    public const FACTOR_SEGURO = 1.18;

    /** Horas de una jornada (para costo por hora). */
    public const HORAS_DIA = 8;

    public const DESCUENTOS_TRABAJADOR = ['plame_descuento_0601_comision_afp_pct', 'plame_descuento_0605_renta_5ta_retenida',
        'plame_descuento_0606_prima_seguro_afp', 'plame_descuento_0607_snp', 'plame_descuento_0608_spp_aporte_obligatorio'];

    public const APORTES_EMPLEADOR = ['plame_aporte_empleador_0803_poliza', 'plame_aporte_empleador_0804_essalud',
        'plame_aporte_empleador_0805_sctr', 'plame_aporte_empleador_0810_eps'];

    /**
     * Proyección del costo del trabajador en el mes (columnas de la hoja PROYECTADA). Un valor es null si falta el
     * parámetro del mes que necesita.
     *
     * @return array<string, ?float>
     */
    public static function proyeccion(array $f, array $m): array
    {
        $num = fn($k) => (float) ($f[$k] ?? 0);
        // D+E+F: básica + bonificación + asignación (sin compensación vacacional)
        $base = $num('remuneracion_basica') + $num('bonificacion') + $num('asignacion_familiar');
        // H: sueldo bruto = básica + bonificación + asignación + compensación vacacional
        $bruto = $num('remuneracion_basica') + $num('bonificacion') + $num('asignacion_familiar') + $num('compensacion_vacacional');
        $pct = fn($k) => $m[$k] ?? null;

        $r = [
            'proyeccion_sueldo_bruto' => $bruto,
            'proyectado_cts' => $pct('cts') === null ? null : $base * ($pct('cts') / 100),
            'proyectado_gratificaciones' => $pct('gratificaciones') === null ? null : $base * ($pct('gratificaciones') / 100),
            'proyectado_beta30' => ($m['rmv'] ?? null) === null || $pct('beta30') === null ? null : $m['rmv'] * ($pct('beta30') / 100),
            'proyectado_essalud' => $pct('essalud') === null ? null : $bruto * ($pct('essalud') / 100),
            'proyectado_vida_ley' => $pct('vida_ley') === null ? null : $bruto * ($pct('vida_ley') / 100) * self::FACTOR_SEGURO,
            'proyectado_pension_sctr' => $pct('pension_sctr') === null ? null : $bruto * ($pct('pension_sctr') / 100) * self::FACTOR_SEGURO,
            'proyectado_essalud_eps' => $pct('essalud_eps') === null ? null : $bruto * ($pct('essalud_eps') / 100) * self::FACTOR_SEGURO,
            // J = H - I
            'proyectado_sueldo_neto' => $bruto - $num('proyectado_dscto_afp_prima_seguro'),
        ];
        // M = L * 6%
        $r['proyectado_essalud_gratificaciones'] = $pct('essalud_gratificaciones') === null || $r['proyectado_gratificaciones'] === null
            ? null : $r['proyectado_gratificaciones'] * ($pct('essalud_gratificaciones') / 100);

        // S = J+K+L+M+N: sueldo neto + beneficios sociales
        $r['proyectado_sueldo_neto_beneficios'] = self::sumaSinNulos([$r['proyectado_sueldo_neto'], $r['proyectado_cts'],
            $r['proyectado_gratificaciones'], $r['proyectado_essalud_gratificaciones'], $r['proyectado_beta30']]);
        // T = H+K+L+M+N+O+P+Q+R: bruto + beneficios + aportes
        $r['proyectado_sueldo_bruto_beneficios_aportes'] = self::sumaSinNulos([$bruto, $r['proyectado_cts'], $r['proyectado_gratificaciones'],
            $r['proyectado_essalud_gratificaciones'], $r['proyectado_beta30'], $r['proyectado_essalud'], $r['proyectado_vida_ley'],
            $r['proyectado_pension_sctr'], $r['proyectado_essalud_eps']]);

        $dias = $m['dias_laborables'] ?? null;
        // U = T / días laborables; V = U / 8
        $r['proyectado_jornal_diario'] = $r['proyectado_sueldo_bruto_beneficios_aportes'] === null || empty($dias)
            ? null : $r['proyectado_sueldo_bruto_beneficios_aportes'] / $dias;
        $r['proyectado_costo_hora'] = $r['proyectado_jornal_diario'] === null ? null : $r['proyectado_jornal_diario'] / self::HORAS_DIA;

        // Z = AA - S: lo que recibe el trabajador menos lo que le correspondería formalmente
        $netoTotal = $f['proyectado_sueldo_neto_total'] ?? null;
        $r['proyectado_diferencia_bonificacion'] = $netoTotal === null || $r['proyectado_sueldo_neto_beneficios'] === null
            ? null : $netoTotal - $r['proyectado_sueldo_neto_beneficios'];
        // AB = T + Z: costo total que asume la empresa
        $r['proyectado_sueldo_bruto_negro'] = $r['proyectado_sueldo_bruto_beneficios_aportes'] === null || $r['proyectado_diferencia_bonificacion'] === null
            ? null : $r['proyectado_sueldo_bruto_beneficios_aportes'] + $r['proyectado_diferencia_bonificacion'];
        // AC = AB / días laborables; AD = AC / 8 (sin redondear)
        $r['proyectado_sueldo_por_dia'] = $r['proyectado_sueldo_bruto_negro'] === null || empty($dias)
            ? null : $r['proyectado_sueldo_bruto_negro'] / $dias;
        $r['proyectado_sueldo_por_hora'] = $r['proyectado_sueldo_por_dia'] === null ? null : $r['proyectado_sueldo_por_dia'] / self::HORAS_DIA;

        return $r;
    }

    /**
     * Lo realmente pagado y su costo, según las horas registradas del mes.
     *
     * @return array{sueldo_pagado: float, aportes_trabajador: float, aportes_empleador: float, pagado_sueldo_bruto_negro: ?float,
     *   pagado_sueldo_por_hora: ?float, pago_jornal_diario: ?float}
     */
    public static function pagado(array $f, array $m): array
    {
        // Sueldo acordado proporcional a las horas registradas (completo si el mes no tiene horas configuradas)
        $horasMeta = $m['total_horas'] ?? 0;
        $horas = $f['plame_total_horas'] ?? 0;
        $acordado = $f['proyectado_sueldo_neto_total'] ?? 0;
        $sueldo = $horasMeta <= 0 ? $acordado : ($acordado / $horasMeta) * $horas;

        $aportesTrabajador = round(array_sum(array_map(fn($k) => $f[$k] ?? 0, self::DESCUENTOS_TRABAJADOR)), 2);
        $aportesEmpleador = round(array_sum(array_map(fn($k) => $f[$k] ?? 0, self::APORTES_EMPLEADOR)), 2);
        // Costo real del mes: sueldo pagado + lo que retiene el PLAME + aportes del empleador
        $costo = $sueldo === null ? null : $sueldo + $aportesTrabajador + $aportesEmpleador;

        $dias = $m['dias_laborables'] ?? null;
        return [
            'sueldo_pagado' => $sueldo,
            'aportes_trabajador' => $aportesTrabajador,
            'aportes_empleador' => $aportesEmpleador,
            'pagado_sueldo_bruto_negro' => $costo,
            // (float): el decimal llega como "0.00" y empty("0.00") es false en PHP
            'pagado_sueldo_por_hora' => $costo === null || (float) $horas <= 0 ? null : $costo / (float) $horas,
            // Jornal del sueldo acordado (base del cálculo de días de vacaciones)
            'pago_jornal_diario' => ($f['proyectado_sueldo_neto_total'] ?? null) === null || empty($dias) ? null : $f['proyectado_sueldo_neto_total'] / $dias,
        ];
    }

    /** Aporte con factor de seguro (vida ley, SCTR, EPS) sobre una base, redondeado; 0 si el mes no tiene el porcentaje. */
    public static function aporteSeguro(float $base, $porcentaje): float
    {
        return is_null($porcentaje) ? 0 : round($base * ($porcentaje / 100) * self::FACTOR_SEGURO, 2);
    }

    private static function sumaSinNulos(array $valores): ?float
    {
        return in_array(null, $valores, true) ? null : array_sum($valores);
    }
}
