<?php

namespace App\Services\Planilla\Plame;

use App\Models\PlanMensualPersonal;
use App\Models\PlanMensualSpDesc;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Conceptos del PLAME de un trabajador, con ajustes manuales.
 *
 * Cada concepto tiene su valor calculado por el sistema; si en "Ajustes PLAME" (o, para el 0118, en Vacaciones y
 * bonos) se le puso un monto a mano, ese manda, y los conceptos que dependen de él se calculan con el ajustado:
 * 0118/0121/0201 → remuneración bruta → gratificación (0406) → bonif. extraordinaria (0312), CTS, descuentos de
 * pensión, EsSalud y neto. Se guardan los dos: las columnas plame_* con el valor final y plame_calculados con el
 * del sistema, para referencia.
 */
class PlanillaPlameProceso
{
    /** Columnas que dependen de otras (no son conceptos con código) */
    private const COLUMNA_BRUTA = 'plame_remuneracion_bruta';
    private const COLUMNA_NETO = 'plame_neto_a_pagar';

    /** 0118: lo que paga la planilla oficial: valor hora (RMV / 240, a 3 decimales) × 8 × días de vacaciones. */
    public static function remuneracionVacacional(float $rmv, int|float $dias): float
    {
        return round(round($rmv / 240, 3) * 8 * $dias, 2);
    }

    /**
     * 0201: solo se descuentan los días de suspensión PERFECTA (faltas, licencias sin goce…). El descanso médico,
     * la licencia con goce y las vacaciones son suspensión imperfecta: se pagan y no reducen la asignación.
     */
    public static function asignacionFamiliar(float $monto, int $diasMes, int|float $diasSuspensionPerfecta): float
    {
        return $diasMes > 0 ? round(($monto / $diasMes) * ($diasMes - $diasSuspensionPerfecta), 2) : 0;
    }

    /**
     * @param array{
     *   calculados: array<string, float>, // lo calculado de 0117, 0118, 0121, 0201, 0803, 0805, 0810
     *   rmv: float, dias_mes: int, dias_suspension_perfecta: int|float,
     *   codigo_sp: ?string, es_pensionista: bool, es_mayor_65: bool,
     *   descuento_sp: ?PlanMensualSpDesc, pct_essalud: ?float
     * } $e
     * @param array<string, array{monto: float|string, motivo?: ?string}> $ajustes
     * @return array{columnas: array<string, float>, calculados: array<string, float>}
     */
    public static function calcular(array $e, array $ajustes = []): array
    {
        $calc = [];
        $final = [];
        // Calcula un concepto, guarda lo calculado y devuelve el valor que vale (el ajustado si lo hay)
        $poner = function (string $codigo, float $calculado) use (&$calc, &$final, $ajustes): float {
            $calc[$codigo] = round($calculado, 2);
            $final[$codigo] = isset($ajustes[$codigo]['monto']) ? round((float) $ajustes[$codigo]['monto'], 2) : $calc[$codigo];
            return $final[$codigo];
        };

        foreach (['0117', '0118', '0121', '0201', '0803', '0805', '0810'] as $codigo) {
            $poner($codigo, (float) ($e['calculados'][$codigo] ?? 0));
        }
        $diasMes = (int) $e['dias_mes'];

        // Remuneración bruta = 0118 + 0121 + 0201
        $bruta = round($final['0118'] + $final['0121'] + $final['0201'], 2);
        // 0406: 16.66% de la remuneración bruta
        $c0406 = $poner('0406', $bruta * 0.1666);
        // 0312: 6% de la gratificación (0406)
        $poner('0312', $c0406 * 0.06);
        // 0904: 9.72% de la remuneración bruta
        $poner('0904', $bruta * 0.0972);
        // 0314 BETA 30%: ((30% * rmv) / días del mes) * (días del mes - suspensión perfecta)
        $poner('0314', $diasMes > 0 ? ((0.30 * $e['rmv']) / $diasMes) * ($diasMes - $e['dias_suspension_perfecta']) : 0);

        // Base imponible de pensión y EsSalud: 0117 + 0118 + 0121 + 0201
        $baseImponible = $final['0117'] + $final['0118'] + $final['0121'] + $final['0201'];
        $sp = $e['descuento_sp'];
        $aplica = !$e['es_pensionista'] && $sp;
        $esSnp = $e['codigo_sp'] === 'SNP';
        // 0601 comisión AFP; 0606 prima (mayores de 65 no pagan); 0607 SNP; 0608 aporte SPP (sobre bruta + 0117)
        $poner('0601', $aplica && !$esSnp ? $sp->comision / 100 * $baseImponible : 0);
        $poner('0605', 0);
        $poner('0606', $aplica && !$esSnp && !$e['es_mayor_65'] ? $sp->prima_seguros / 100 * $baseImponible : 0);
        $poner('0607', $aplica && $esSnp ? $sp->aporte_obligatorio / 100 * $baseImponible : 0);
        $poner('0608', $aplica && !$esSnp ? $sp->aporte_obligatorio / 100 * ($bruta + $final['0117']) : 0);
        // 0804 EsSalud del PLAME: sobre la base imponible
        $poner('0804', $e['pct_essalud'] === null ? 0 : $baseImponible * ($e['pct_essalud'] / 100));

        // Neto a pagar = ingresos - descuentos del trabajador (cada concepto ya redondeado, como la boleta R08)
        $neto = 0;
        foreach (PlanillaPlameReglas::INGRESOS as $codigo => $_) {
            $neto += $final[$codigo];
        }
        foreach (PlanillaPlameReglas::DESCUENTOS as $codigo => $_) {
            $neto -= $final[$codigo];
        }

        $columnas = [self::COLUMNA_BRUTA => $bruta, self::COLUMNA_NETO => round($neto, 2)];
        foreach ($final as $codigo => $valor) {
            $columnas[PlanillaPlameReglas::columna($codigo)] = $valor;
        }
        return ['columnas' => $columnas, 'calculados' => $calc];
    }

    /**
     * Recalcula en cadena un trabajador ya generado (al cambiar sus ajustes), con los datos guardados del mes.
     * También el exceso de vacaciones pagado en negro.
     */
    public function recalcular(PlanMensualPersonal $p): void
    {
        $plan = $p->planMensual;
        $diasSuspensionPerfecta = 0;
        foreach (['01', '02', '03', '04', '05', '06', '07', '08'] as $codigo) {
            $diasSuspensionPerfecta += (int) $p->{"sp_{$codigo}"};
        }

        // Lo calculado por el sistema; si el mes se generó antes de guardarse, la columna (sin ajuste) es lo calculado
        $calculados = [];
        foreach (['0117', '0118', '0121', '0201', '0803', '0805', '0810'] as $codigo) {
            $calculados[$codigo] = $p->calculadoPlame($codigo) ?? (float) $p->{PlanillaPlameReglas::columna($codigo)};
        }

        $r = self::calcular([
            'calculados' => $calculados,
            'rmv' => (float) $plan->rmv,
            'dias_mes' => Carbon::create($plan->anio, $plan->mes, 1)->daysInMonth,
            'dias_suspension_perfecta' => $diasSuspensionPerfecta,
            'codigo_sp' => $p->sistema_pension,
            'es_pensionista' => (bool) $p->es_pensionista,
            'es_mayor_65' => (int) $p->edad >= 65,
            'descuento_sp' => PlanMensualSpDesc::where('plan_mensual_id', $plan->id)->where('codigo', $p->sistema_pension)->first(),
            'pct_essalud' => $plan->essalud === null ? null : (float) $plan->essalud,
        ], $p->plame_ajustes ?? []);

        $p->update($r['columnas'] + ['plame_calculados' => $r['calculados']] + $this->camposVacaciones($p, $r['columnas']));
    }

    /** Campos de vacaciones que dependen del 0118 final: el personalizado (espejo del ajuste) y el exceso en negro. */
    public function camposVacaciones(PlanMensualPersonal $p, array $columnas): array
    {
        $final0118 = $columnas[PlanillaPlameReglas::columna('0118')];
        $campos = ['vacaciones_plame_personalizado' => $p->ajustePlame('0118')['monto'] ?? null];
        if ($p->vacaciones_neto_pagadas !== null) {
            $campos['vacaciones_negro'] = round((float) $p->vacaciones_neto_pagadas - $final0118, 2);
        }
        return $campos;
    }

    /**
     * Pone (o quita, con monto null) el ajuste manual de un concepto y recalcula la cadena. Único punto de escritura
     * de los ajustes: lo usan la pestaña "Ajustes PLAME" y la columna de vacaciones personalizadas.
     */
    public function guardarAjuste(PlanMensualPersonal $p, string $codigo, $monto, ?string $motivo = null): void
    {
        if (!PlanillaPlameReglas::columna($codigo) || in_array($codigo, ['0605'], true)) {
            throw ValidationException::withMessages(['codigo' => "El concepto {$codigo} no se puede ajustar."]);
        }
        $ajustes = $p->plame_ajustes ?? [];
        $antes = $ajustes[$codigo] ?? null;

        if ($monto === null || $monto === '') {
            unset($ajustes[$codigo]);
        } else {
            if (!is_numeric($monto) || (float) $monto < 0) {
                throw ValidationException::withMessages(['monto' => 'El monto debe ser un número mayor o igual a cero.']);
            }
            $ajustes[$codigo] = ['monto' => round((float) $monto, 2), 'motivo' => $motivo !== null && trim($motivo) !== '' ? trim($motivo) : null];
        }

        if ($antes === ($ajustes[$codigo] ?? null)) {
            return;
        }
        $p->plame_ajustes = $ajustes ?: null;
        $p->save();
        $this->recalcular($p->refresh());

        AuditoriaServicio::registrar(PlanMensualPersonal::class, $p->id, 'editar', [$codigo => $antes], [$codigo => $ajustes[$codigo] ?? null],
            "Ajuste PLAME {$codigo} de {$p->nombres}");
    }
}
