<?php

namespace App\Services\Planilla\Parametro;

use Illuminate\Support\Carbon;

/**
 * Parámetros de planilla que necesita el sistema (sin base de datos). Sus valores con vigencia están en
 * configuracion_historial (y las AFP en plan_prima_comision_historicos); las planillas los leen de ahí, sin copiarlos.
 *
 * Regla de vigencia: el valor de un mes es el vigente el día 1 de ese mes. Por eso un valor nuevo rige desde un mes
 * (se registra con "desde" el día 1).
 */
class PlanillaParametroReglas
{
    /** codigo => [nombre, unidad (% o S/), planillas que lo usan, para qué sirve] */
    public const CATALOGO = [
        'rmv' => ['Remuneración mínima vital (RMV)', 'S/', ['agraria', 'oficina'], 'Sueldo base del mes, BETA 30 %, remuneración vacacional (0118) y básica de oficina sin sueldo.'],
        'asignacion_familiar' => ['Asignación familiar', 'S/', ['agraria', 'oficina'], 'Monto mensual para quien tiene hijos con derecho (0201).'],
        'gratificaciones' => ['Gratificaciones', '%', ['agraria'], 'Provisión mensual de gratificación (régimen agrario).'],
        'essalud_gratificaciones' => ['EsSalud de gratificaciones', '%', ['agraria'], 'Bonificación extraordinaria sobre la gratificación (0312).'],
        'cts' => ['CTS', '%', ['agraria'], 'Provisión mensual de CTS (0904).'],
        'beta30' => ['BETA 30 %', '%', ['agraria'], 'Bonificación especial por trabajo agrario sobre la RMV (0314).'],
        'essalud' => ['EsSalud agrario', '%', ['agraria'], 'Aporte del empleador al EsSalud (0804), régimen agrario.'],
        'vida_ley' => ['Vida ley', '%', ['agraria', 'oficina'], 'Póliza de seguro de vida (0803); se aplica con el factor 1.18.'],
        'pension_sctr' => ['Pensión SCTR', '%', ['agraria'], 'Seguro complementario de trabajo de riesgo (0805), con factor 1.18.'],
        'essalud_eps' => ['EsSalud EPS', '%', ['agraria'], 'Aporte a EPS (0810), con factor 1.18.'],
        'descuento_snp' => ['Descuento SNP (ONP)', '%', ['agraria', 'oficina'], 'Aporte del trabajador al Sistema Nacional de Pensiones (0607).'],
        'essalud_general' => ['EsSalud régimen general', '%', ['oficina'], 'Aporte del empleador al EsSalud de la planilla oficina.'],
        'bonif_extraordinaria_general' => ['Bonificación extraordinaria (general)', '%', ['oficina'], 'Sobre la gratificación de la planilla oficina.'],
        'retencion_cuarta' => ['Retención de 4ta categoría', '%', ['oficina'], 'Retención a recibos por honorarios.'],
        'tope_retencion_cuarta' => ['Tope de retención de 4ta', 'S/', ['oficina'], 'Monto del recibo por honorarios desde el que se retiene.'],
    ];

    /** AFP cuyas comisiones y primas se necesitan cada mes (página Descuentos AFP) */
    public const AFP = ['HABITAT', 'INTEGRA', 'PRIMA', 'PROFUTURO'];

    public const PLANILLAS = ['agraria' => 'Planilla agraria', 'oficina' => 'Planilla oficina'];

    /** @return string[] códigos que necesita una planilla */
    public static function codigos(?string $planilla = null): array
    {
        return array_keys(array_filter(self::CATALOGO, fn($p) => $planilla === null || in_array($planilla, $p[2], true)));
    }

    public static function nombre(string $codigo): string
    {
        return self::CATALOGO[$codigo][0] ?? $codigo;
    }

    public static function unidad(string $codigo): string
    {
        return self::CATALOGO[$codigo][1] ?? '';
    }

    /** Día que representa al mes (el valor vigente ese día es el del mes). */
    public static function fechaDelMes(int $mes, int $anio): string
    {
        return Carbon::create($anio, $mes, 1)->toDateString();
    }

    /** "Desde" de un valor nuevo: siempre el día 1 del mes elegido (acepta 2026-09 o una fecha). */
    public static function desde(string $mesOFecha): string
    {
        $f = strlen(trim($mesOFecha)) === 7 ? Carbon::createFromFormat('Y-m-d', trim($mesOFecha) . '-01') : Carbon::parse($mesOFecha);
        return $f->startOfMonth()->toDateString();
    }

    public static function formato(string $codigo, $valor): string
    {
        if ($valor === null) {
            return '—';
        }
        $n = rtrim(rtrim(number_format((float) $valor, 4, '.', ','), '0'), '.');
        return self::unidad($codigo) === 'S/' ? 'S/ ' . number_format((float) $valor, 2) : $n . ' %';
    }
}
