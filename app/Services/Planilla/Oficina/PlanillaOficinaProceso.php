<?php

namespace App\Services\Planilla\Oficina;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanMensual;
use App\Models\PlanMensualPersonal;
use App\Models\PlanMensualSpDesc;
use App\Models\PlanOficinaPersonal;
use App\Models\PlanSuspension;
use App\Services\Planilla\PlanillaMensualServicio;
use App\Services\Reporte\AuditoriaServicio;
use App\Services\Sistema\ConfiguracionHistorialServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Planilla oficina (régimen general), como las hojas EMPLEADOS y ADM de la planilla oficial. Entran todos los que
 * tienen contrato de tipo "oficina" o "general" en el mes.
 *
 * En planilla (5ta categoría):
 * - Sueldo: remuneración básica menos vacaciones y faltas (sueldo / 30 por día); las vacaciones van aparte (0118).
 *   La asignación familiar se paga completa.
 * - Descuentos: AFP (fondo, comisión, prima; mayores de 65 sin prima) o SNP con las tasas del mes; 5ta categoría a mano.
 * - Aportes: EsSalud del régimen general (9%) y vida ley (tasa × 1.18), sobre el total de remuneraciones.
 * - Beneficios. Lo que corresponde a cada mes: CTS = (rem. + asig. + 1/6 de gratificación) / 12; gratificación =
 *   (rem. + asig.) × (1 + bonif. extraordinaria) / 6. Según el contrato se pagan:
 *     - en dos tramos (lo normal): se retienen cada mes y se pagan la gratificación en julio y diciembre y la CTS en
 *       mayo y noviembre;
 *     - cada mes con el sueldo (beneficios_mensuales): en julio, diciembre, mayo y noviembre no hay pago aparte.
 *   El costo real y el negro son los mismos en los dos casos; cambia el mes en que sale la plata.
 * - Negro = sueldo real − neto − lo que corresponde de beneficios al mes.
 *
 * Por honorarios (4ta categoría, recibo por honorarios): retención de 4ta (8% si el recibo pasa de S/ 1,500, salvo
 * suspensión), sin EsSalud, AFP, vida ley ni beneficios. Negro = sueldo real − neto.
 *
 * Todo lo manual (5ta categoría, sueldo real, excepciones) es un ajuste por columna, como en la planilla agraria.
 */
class PlanillaOficinaProceso
{
    /** Columnas que se pueden ajustar a mano => etiqueta */
    public const AJUSTABLES = [
        'rem_sueldo' => 'Sueldo por los días (u honorarios)',
        'rem_vacaciones' => 'Vacaciones (0118)',
        'rem_asignacion_familiar' => 'Asignación familiar (0201)',
        'desc_afp_fondo' => 'AFP fondo',
        'desc_afp_comision' => 'AFP comisión',
        'desc_afp_prima' => 'AFP prima de seguro',
        'desc_snp' => 'SNP',
        'desc_renta_quinta' => 'Renta 5ta categoría',
        'desc_renta_cuarta' => 'Retención 4ta categoría (honorarios)',
        'aporte_essalud' => 'EsSalud',
        'aporte_vida_ley' => 'Vida ley',
        'provision_cts' => 'CTS del mes (retener)',
        'provision_gratificacion' => 'Gratificación del mes (retener)',
        'gratificacion' => 'Gratificación pagada',
        'bonif_extraordinaria' => 'Bonificación extraordinaria pagada',
        'cts' => 'CTS pagada',
        'sueldo_real' => 'Sueldo real (acordado)',
    ];

    public const MESES_GRATIFICACION = [7, 12];
    public const MESES_CTS = [5, 11];
    public const TIPOS_PLANILLA = ['oficina', 'general'];

    /**
     * @param array{tipo_ingreso: string, beneficios_mensuales: bool, rem_basica: float, asig: float, dias_vacaciones: int,
     *   dias_perfecta: int, mes: int, meses_semestre: int, codigo_sp: ?string, es_pensionista: bool, es_mayor_65: bool,
     *   descuento_sp: ?PlanMensualSpDesc, pct_essalud: float, pct_vida_ley: ?float, pct_bonif: float,
     *   pct_cuarta: float, tope_cuarta: float, suspension_cuarta: bool, sueldo_real: ?float} $e
     * @param array<string, array{monto: mixed, motivo?: ?string}> $ajustes
     * @return array{columnas: array<string, ?float>, calculados: array<string, ?float>}
     */
    public static function calcular(array $e, array $ajustes = []): array
    {
        $calc = [];
        $fin = [];
        $poner = function (string $col, ?float $valor) use (&$calc, &$fin, $ajustes): ?float {
            $calc[$col] = $valor === null ? null : round($valor, 2);
            $fin[$col] = isset($ajustes[$col]['monto']) ? round((float) $ajustes[$col]['monto'], 2) : $calc[$col];
            return $fin[$col];
        };
        $honorarios = $e['tipo_ingreso'] === 'honorarios';

        $base = $e['rem_basica'];
        $diario = $base / 30;
        $vac = $poner('rem_vacaciones', $honorarios ? 0 : $diario * $e['dias_vacaciones']);
        $sueldo = $poner('rem_sueldo', max(0, $base - ($honorarios ? 0 : $diario * $e['dias_vacaciones']) - $diario * $e['dias_perfecta']));
        $asig = $poner('rem_asignacion_familiar', $honorarios ? 0 : $e['asig']);
        $total = round($sueldo + $vac + $asig, 2);

        $sp = $e['descuento_sp'];
        $aplica = !$honorarios && !$e['es_pensionista'] && $sp;
        $esSnp = $e['codigo_sp'] === 'SNP';
        $fondo = $poner('desc_afp_fondo', $aplica && !$esSnp ? $sp->aporte_obligatorio / 100 * $total : 0);
        $comision = $poner('desc_afp_comision', $aplica && !$esSnp ? $sp->comision / 100 * $total : 0);
        $prima = $poner('desc_afp_prima', $aplica && !$esSnp && !$e['es_mayor_65'] ? $sp->prima_seguros / 100 * $total : 0);
        $snp = $poner('desc_snp', $aplica && $esSnp ? $sp->aporte_obligatorio / 100 * $total : 0);
        $quinta = $poner('desc_renta_quinta', 0);
        $cuarta = $poner('desc_renta_cuarta', $honorarios && !$e['suspension_cuarta'] && $total > $e['tope_cuarta'] ? $total * $e['pct_cuarta'] / 100 : 0);
        $descuentos = round($fondo + $comision + $prima + $snp + $quinta + $cuarta, 2);
        $neto = round($total - $descuentos, 2);

        $essalud = $poner('aporte_essalud', $honorarios ? 0 : $total * $e['pct_essalud'] / 100);
        $vidaLey = $poner('aporte_vida_ley', $honorarios || $e['pct_vida_ley'] === null ? 0 : $total * $e['pct_vida_ley'] / 100 * PlanMensualPersonal::FACTOR_SEGURO);

        // Beneficios: base = remuneración mensual completa (básica + asignación), no la de los días del mes
        $remMensual = $honorarios ? 0 : $base + $e['asig'];
        $factorBonif = $e['pct_bonif'] / 100;
        $provCts = $poner('provision_cts', ($remMensual + $remMensual / 6) / 12);
        $provGrati = $poner('provision_gratificacion', $remMensual * (1 + $factorBonif) / 6);
        $dosTramos = !$honorarios && !$e['beneficios_mensuales'];
        $grati = $poner('gratificacion', $dosTramos && in_array($e['mes'], self::MESES_GRATIFICACION, true) ? $remMensual / 6 * $e['meses_semestre'] : 0);
        $bonif = $poner('bonif_extraordinaria', $grati * $factorBonif);
        $cts = $poner('cts', $dosTramos && in_array($e['mes'], self::MESES_CTS, true) ? ($remMensual + $remMensual / 6) / 12 * $e['meses_semestre'] : 0);
        // Lo que sale este mes por beneficios: el pago del tramo, o lo del mes si se paga cada mes
        $pagados = round($dosTramos ? $grati + $bonif + $cts : ($honorarios ? 0 : $provCts + $provGrati), 2);

        $real = $poner('sueldo_real', $e['sueldo_real']);
        $negro = $real === null ? 0 : round($real - $neto - $provCts - $provGrati, 2);

        $columnas = $fin + [
            'total_remuneracion' => $total,
            'total_descuentos' => $descuentos,
            'neto_planilla' => $neto,
            'beneficios_pagados' => $pagados,
            'bonificacion_negro' => $negro,
            'pago_blanco_mes' => round($neto + $pagados, 2),
            // Costo contable (hoja EMPLEADOS, columna AD): remuneraciones + aportes + beneficios pagados en el mes + 5ta
            'costo_contable' => round($total + $essalud + $vidaLey + $pagados + $quinta, 2),
            // Costo blanco + negro (columna AN): sueldo real + aportes + 5ta
            'costo_total' => round(($real ?? $neto) + $essalud + $vidaLey + $quinta, 2),
        ];
        return ['columnas' => $columnas, 'calculados' => $calc];
    }

    /**
     * Genera (o regenera) la planilla oficina del mes con los contratos de oficina/general vigentes. Conserva los
     * ajustes y el comprobante de cada persona.
     *
     * @return array{plan: PlanMensual, personas: int, avisos: string[]}
     */
    public function generar(int $mes, int $anio): array
    {
        $plan = $this->prepararMes($mes, $anio);
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = $inicio->copy()->endOfMonth();

        return DB::transaction(function () use ($plan, $mes, $anio, $inicio, $finMes) {
            $avisos = [];
            $existentes = PlanOficinaPersonal::where('plan_mensual_id', $plan->id)->get()->keyBy('plan_empleado_id');
            $contratos = PlanContrato::with('empleado')->whereIn('tipo_planilla', self::TIPOS_PLANILLA)
                ->whereDate('fecha_inicio', '<=', $finMes)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))
                ->orderBy('fecha_inicio')->get()->keyBy('plan_empleado_id');

            // Primero los de honorarios, luego por nombre
            $ordenados = $contratos->filter(fn($c) => $c->empleado)
                ->sortBy(fn($c) => ($c->tipo_ingreso === 'honorarios' ? '0' : '1') . $c->empleado->nombre_completo);
            $orden = 0;
            foreach ($ordenados as $empleadoId => $contrato) {
                $empleado = $contrato->empleado;
                if ($contrato->tipo_ingreso !== 'honorarios' && !$contrato->plan_sp_codigo && !$contrato->esta_jubilado) {
                    $avisos[] = "{$empleado->nombre_completo}: el contrato no tiene sistema de pensión.";
                }
                if ((float) $contrato->remuneracion_basica <= 0 && $contrato->tipo_ingreso === 'honorarios') {
                    $avisos[] = "{$empleado->nombre_completo}: el contrato por honorarios no tiene monto (remuneración básica).";
                }
                $fila = $existentes->get($empleadoId) ?? new PlanOficinaPersonal(['plan_mensual_id' => $plan->id, 'plan_empleado_id' => $empleadoId]);
                $fila->fill($this->datosFila($plan, $empleado, $contrato, $mes, $anio, $fila->ajustes ?? []) + ['orden' => ++$orden]);
                $fila->save();
            }

            // Los que ya no tienen contrato de oficina/general este mes salen
            PlanOficinaPersonal::where('plan_mensual_id', $plan->id)->whereNotIn('plan_empleado_id', $ordenados->keys())->delete();

            return ['plan' => $plan, 'personas' => $ordenados->count(), 'avisos' => $avisos];
        });
    }

    /** Recalcula una fila con sus datos guardados (al cambiar un ajuste). */
    public function recalcular(PlanOficinaPersonal $p): void
    {
        $plan = $p->planMensual;
        $contrato = $p->planEmpleado?->contrato($plan->mes, $plan->anio);
        $p->update($this->calcularFila($plan, $p, $contrato, $p->ajustes ?? []));
    }

    /** Pone o quita (monto null) un ajuste y recalcula. */
    public function guardarAjuste(PlanOficinaPersonal $p, string $columna, $monto, ?string $motivo = null): void
    {
        if (!array_key_exists($columna, self::AJUSTABLES)) {
            throw ValidationException::withMessages(['ajusteColumna' => 'Ese concepto no se puede ajustar.']);
        }
        if ($monto !== null && $monto !== '' && (!is_numeric($monto) || (float) $monto < 0)) {
            throw ValidationException::withMessages(['ajusteMonto' => 'El monto debe ser un número mayor o igual a cero.']);
        }
        $ajustes = $p->ajustes ?? [];
        $antes = $ajustes[$columna] ?? null;
        if ($monto === null || $monto === '') {
            unset($ajustes[$columna]);
        } else {
            $ajustes[$columna] = ['monto' => round((float) $monto, 2), 'motivo' => $motivo !== null && trim($motivo) !== '' ? trim($motivo) : null];
        }
        if ($antes === ($ajustes[$columna] ?? null)) {
            return;
        }
        $p->ajustes = $ajustes ?: null;
        $p->save();
        $this->recalcular($p->refresh());
        AuditoriaServicio::registrar(PlanOficinaPersonal::class, $p->id, 'editar', [$columna => $antes], [$columna => $ajustes[$columna] ?? null],
            "Ajuste planilla oficina {$columna} de {$p->nombres}");
    }

    /** N° del recibo por honorarios (u otro comprobante) del mes. */
    public function guardarComprobante(PlanOficinaPersonal $p, ?string $comprobante): void
    {
        $p->update(['comprobante' => $comprobante !== null && trim($comprobante) !== '' ? trim($comprobante) : null]);
    }

    // ------------------------------------------------------------------ apoyo

    /** Mes de planilla con sus tasas: si aún no existe se abre con los parámetros vigentes. */
    public function prepararMes(int $mes, int $anio): PlanMensual
    {
        $plan = PlanMensual::where('mes', $mes)->where('anio', $anio)->first()
            ?? PlanillaMensualServicio::guardarConfiguracionDesdeParametros($mes, $anio);
        // Tasas que usa la planilla oficina; si el mes se abrió sin parámetros, se toman los vigentes a la fecha
        $codigos = ['rmv', 'asignacion_familiar', 'vida_ley', 'essalud_general', 'bonif_extraordinaria_general', 'retencion_cuarta', 'tope_retencion_cuarta'];
        $faltan = array_values(array_filter($codigos, fn($c) => $plan->{$c} === null));
        if ($faltan) {
            $plan->update(ConfiguracionHistorialServicio::obtenerValoresVigentes($faltan, $mes, $anio));
        }
        if (!PlanMensualSpDesc::where('plan_mensual_id', $plan->id)->exists()) {
            PlanillaMensualServicio::snapshotDescuentosSp($plan->id, $mes, $anio);
        }
        return $plan->fresh();
    }

    private function datosFila(PlanMensual $plan, PlanEmpleado $empleado, PlanContrato $contrato, int $mes, int $anio, array $ajustes): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fin = $inicio->copy()->endOfMonth()->startOfDay();
        [$vacaciones, $perfecta] = $this->diasSuspension($empleado->id, $inicio, $fin);
        $honorarios = $contrato->tipo_ingreso === 'honorarios';
        $tieneAsig = !$honorarios && $empleado->cantidadHijosConAsignacionFamiliar($mes, $anio) > 0;

        $fila = new PlanOficinaPersonal([
            'nombres' => $empleado->nombre_completo,
            'cargo' => $empleado->nombre_cargo_actual ?? null,
            'tipo_ingreso' => $contrato->tipo_ingreso ?: 'planilla',
            'beneficios_mensuales' => (bool) $contrato->beneficios_mensuales,
            'sistema_pension' => $honorarios ? null : $contrato->plan_sp_codigo,
            'es_pensionista' => (bool) $contrato->esta_jubilado,
            'edad' => $empleado->edadContable($mes, $anio),
            // Sin remuneración básica en el contrato: la RMV del mes
            'remuneracion_basica' => (float) $contrato->remuneracion_basica > 0 ? (float) $contrato->remuneracion_basica : (float) $plan->rmv,
            'asignacion_familiar' => $tieneAsig ? (float) $plan->asignacion_familiar : 0,
            'cuenta_principal' => $this->textoCuenta($contrato->banco, $contrato->moneda_cuenta, $contrato->numero_cuenta, $contrato->metodo_pago),
            'cuenta_secundaria' => $this->textoCuenta($contrato->banco_secundario, $contrato->moneda_cuenta_secundaria, $contrato->numero_cuenta_secundaria),
            'dias_mes' => $inicio->daysInMonth,
            'dias_vacaciones' => $vacaciones,
            'dias_suspension_perfecta' => $perfecta,
            'dias_laborados' => max(0, $inicio->daysInMonth - $vacaciones - $perfecta),
        ]);
        // Sueldo real: el del empleado (pestaña Sueldos); por honorarios, si no tiene, el monto del recibo
        $sueldoReal = $empleado->sueldo($mes, $anio) ?? ($honorarios ? (float) $fila->remuneracion_basica : null);
        return array_merge($fila->getAttributes(), $this->calcularFila($plan, $fila, $contrato, $ajustes, $sueldoReal));
    }

    private function calcularFila(PlanMensual $plan, PlanOficinaPersonal $p, ?PlanContrato $contrato, array $ajustes, ?float $sueldoReal = null): array
    {
        $r = self::calcular([
            'tipo_ingreso' => $p->tipo_ingreso ?: 'planilla',
            'beneficios_mensuales' => (bool) $p->beneficios_mensuales,
            'rem_basica' => (float) $p->remuneracion_basica,
            'asig' => (float) $p->asignacion_familiar,
            'dias_vacaciones' => (int) $p->dias_vacaciones,
            'dias_perfecta' => (int) $p->dias_suspension_perfecta,
            'mes' => (int) $plan->mes,
            'meses_semestre' => $this->mesesSemestre($contrato, (int) $plan->mes, (int) $plan->anio),
            'codigo_sp' => $p->sistema_pension,
            'es_pensionista' => (bool) $p->es_pensionista,
            'es_mayor_65' => (int) $p->edad >= 65,
            'descuento_sp' => $p->sistema_pension ? PlanMensualSpDesc::where('plan_mensual_id', $plan->id)->where('codigo', $p->sistema_pension)->first() : null,
            'pct_essalud' => (float) ($plan->essalud_general ?? 9),
            'pct_vida_ley' => $plan->vida_ley === null ? null : (float) $plan->vida_ley,
            'pct_bonif' => (float) ($plan->bonif_extraordinaria_general ?? 9),
            'pct_cuarta' => (float) ($plan->retencion_cuarta ?? 8),
            'tope_cuarta' => (float) ($plan->tope_retencion_cuarta ?? 1500),
            'suspension_cuarta' => (bool) $contrato?->suspension_cuarta,
            'sueldo_real' => $sueldoReal ?? $p->calculado('sueldo_real') ?? ($p->sueldo_real !== null ? (float) $p->sueldo_real : null),
        ], $ajustes);
        return $r['columnas'] + ['calculados' => $r['calculados']];
    }

    private function textoCuenta(?string $banco, ?string $moneda, ?string $numero, ?string $metodo = null): ?string
    {
        if (!$numero) {
            return $metodo && $metodo !== 'transferencia' ? ucfirst($metodo) : null;
        }
        return trim(($banco ?? '') . ($moneda === 'USD' ? ' $' : '') . ' ' . $numero);
    }

    /** Meses del semestre (ene–jun / jul–dic para gratificación; nov–abr / may–oct para CTS) trabajados con el contrato. */
    private function mesesSemestre(?PlanContrato $contrato, int $mes, int $anio): int
    {
        $finSemestre = in_array($mes, self::MESES_GRATIFICACION, true)
            ? Carbon::create($anio, $mes, 1)->endOfMonth()
            : Carbon::create($anio, $mes, 1)->subMonth()->endOfMonth();
        $inicioSemestre = $finSemestre->copy()->subMonths(5)->startOfMonth();
        if (!$contrato || Carbon::parse($contrato->fecha_inicio)->lte($inicioSemestre)) {
            return 6;
        }
        // Meses completos desde el ingreso (el mes de ingreso cuenta si entró el día 1)
        $ingreso = Carbon::parse($contrato->fecha_inicio);
        $desde = $ingreso->day === 1 ? $ingreso->copy()->startOfMonth() : $ingreso->copy()->addMonthNoOverflow()->startOfMonth();
        return max(0, min(6, (int) $desde->diffInMonths($finSemestre->copy()->addDay())));
    }

    /** @return array{0: int, 1: int} días de vacaciones (23) y de suspensión perfecta (01–08) en el mes */
    private function diasSuspension(int $empleadoId, Carbon $inicio, Carbon $fin): array
    {
        $vac = 0;
        $perfecta = 0;
        $lista = PlanSuspension::with('tipoSuspension')->where('plan_empleado_id', $empleadoId)
            ->whereDate('fecha_inicio', '<=', $fin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))->get();
        foreach ($lista as $s) {
            $desde = $s->fecha_inicio->copy()->startOfDay()->max($inicio);
            $hasta = ($s->fecha_fin ?? $fin)->copy()->startOfDay()->min($fin);
            $dias = $hasta->lt($desde) ? 0 : $desde->diffInDays($hasta) + 1;
            if ($s->tipoSuspension?->codigo === '23') {
                $vac += $dias;
            } elseif ($s->tipoSuspension?->grupo === 'SP') {
                $perfecta += $dias;
            }
        }
        return [$vac, $perfecta];
    }
}
