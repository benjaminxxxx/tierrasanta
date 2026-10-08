<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\ConsolidadoRiego;
use App\Models\CuadActividadBono;
use App\Models\CuadDetalleHora;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualPersonal;
use App\Models\ReporteDiarioRiego;
use App\Services\Planilla\Asistencia\PlanillaAsistenciaLaborConsulta;
use App\Support\CalculoHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Filas de mano de obra para resumen_costo_diarios (BDD de costos): planilla, riego, cuadrilla y bonos.
 *
 * PLANILLA — la BDD registra lo que pasó en campo; con los datos bien cargados, lo pagado al trabajador en el mes
 * (sueldo proporcional + aportes) es igual a lo que suma la BDD. Lo que no cuadra se ve en el cierre (no se fuerza):
 *   tarifa = costo pagado del mes / horas del registro diario del mes (horas pagadas).
 *   Por cada día del registro diario:
 *   - Detalle por campo: tarifa × horas del tramo.
 *   - Horas pagadas del día sin detalle por campo: van a FDM. Si el día no es "A" (descanso médico, feriado,
 *     licencia con goce…) con la labor vinculada a ese tipo de asistencia (como en la BDD de la empresa:
 *     "FDM, 8 h, labor 97"); si es "A", como horas asistidas sin detalle (revisar).
 *   - Riego: con reporte de riego, el costo sale de sus horas en cada campo (jornal ponderado y uso de horas
 *     acumuladas) en vez del tramo de riego (labor 81 en FDM) del registro diario. Si el reporte y el registro diario
 *     no tienen las mismas horas (riego desincronizado), la diferencia queda a la vista en el cierre.
 *   Cada día cuya suma no es igual a sus horas pagadas queda en diasConDiferencia() con su causa, para el arqueo.
 *
 * Los bonos (productividad de planilla; cuadrilla con jornal o aparte) van en sus propios tipos.
 *
 * Todo el rango se carga una vez (precargar) y cada campaña/campo toma lo suyo: antes se consultaba lo mismo por
 * cada campaña (65 vueltas en un mes).
 */
class ConsolidarCostoPlanillaServicio
{
    public const LABOR_RIEGO = '81';
    public const CAMPO_FDM = 'FDM';
    public const CONCEPTO_SIN_DETALLE = 'Horas asistidas sin detalle de campo';

    private ?\App\Services\Campo\Labor\CampoLaborVigenciaConsulta $vigencias = null;

    /** Rango precargado [inicio, fin] */
    private ?array $rango = null;
    /** campo => filas de planilla (con fecha) ya calculadas */
    private array $planillaPorCampo = [];
    /** campo => filas de bono de productividad */
    private array $bonoProductividadPorCampo = [];
    /** campo => filas de cuadrilla (detalle, riego de cuadrilleros y bonos) */
    private array $cuadrillaPorCampo = [];
    /** Días de planilla cuya suma en la BDD no es igual a las horas pagadas (para el arqueo) */
    private array $diasConDiferencia = [];

    private function vigencias(): \App\Services\Campo\Labor\CampoLaborVigenciaConsulta
    {
        return $this->vigencias ??= new \App\Services\Campo\Labor\CampoLaborVigenciaConsulta();
    }

    /**
     * @param array|null $tipos null = todo; 'planilla' = planilla, riego y cuadrilla; 'bono_productividad'
     */
    public function generarFilas(string $campania, string $campo, string $fechaInicio, ?string $fechaFin = null, ?array $tipos = null): array
    {
        $fechaInicio = Carbon::parse($fechaInicio)->toDateString();
        $fechaFin = Carbon::parse($fechaFin ?? now())->toDateString();
        // Sin precarga que cubra el rango (p. ej. "Consolidar campaña" de todo un año): mes por mes, para no
        // cargar un año entero de todos los campos en memoria
        if ((!$this->rango || $fechaInicio < $this->rango[0] || $fechaFin > $this->rango[1])
            && Carbon::parse($fechaInicio)->format('Y-m') !== Carbon::parse($fechaFin)->format('Y-m')) {
            $filas = [];
            for ($m = Carbon::parse($fechaInicio)->startOfMonth(); $m->toDateString() <= $fechaFin; $m->addMonth()) {
                $desde = max($fechaInicio, $m->toDateString());
                $hasta = min($fechaFin, $m->copy()->endOfMonth()->toDateString());
                $this->precargar($m->toDateString(), $m->copy()->endOfMonth()->toDateString());
                $filas = array_merge($filas, $this->generarFilas($campania, $campo, $desde, $hasta, $tipos));
            }
            return $filas;
        }
        $this->asegurarPrecarga($fechaInicio, $fechaFin);
        $todos = $tipos === null;

        $tomar = function (array $porCampo) use ($campo, $fechaInicio, $fechaFin, $campania): array {
            $filas = [];
            foreach ($porCampo[$campo] ?? [] as $fila) {
                if ($fila['fecha'] >= $fechaInicio && $fila['fecha'] <= $fechaFin) {
                    $fila['campania'] = $campania;
                    $filas[] = $fila;
                }
            }
            return $filas;
        };

        $filas = [];
        if ($todos || in_array('planilla', $tipos, true)) {
            $filas = array_merge($filas, $tomar($this->planillaPorCampo), $tomar($this->cuadrillaPorCampo));
        }
        if ($todos || in_array('bono_productividad', $tipos, true)) {
            $filas = array_merge($filas, $tomar($this->bonoProductividadPorCampo));
        }
        return $filas;
    }

    /** Campos que tienen alguna fila en el rango precargado (incluye FDM si hay horas sin detalle). */
    public function camposPrecargados(): array
    {
        return array_fill_keys(array_keys($this->planillaPorCampo + $this->cuadrillaPorCampo + $this->bonoProductividadPorCampo), true);
    }

    /** Carga y calcula todo el rango una sola vez. */
    public function precargar(string $fechaInicio, string $fechaFin): void
    {
        $this->rango = [$fechaInicio, $fechaFin];
        $this->planillaPorCampo = $this->bonoProductividadPorCampo = $this->cuadrillaPorCampo = $this->diasConDiferencia = [];

        $this->calcularPlanilla($fechaInicio, $fechaFin);
        $this->calcularBonoProductividad($fechaInicio, $fechaFin);
        $this->calcularCuadrilla($fechaInicio, $fechaFin);
    }

    /**
     * Días de planilla del rango en que la BDD no suma las horas pagadas del día, con la causa.
     *
     * @return array<int, array{plan_empleado_id:int, trabajador:string, fecha:string, horas_pagadas:float, horas_bdd:float,
     *   diferencia_horas:float, costo:float, causa:string, sincronizado:?bool}>
     */
    public function diasConDiferencia(string $fechaInicio, string $fechaFin): array
    {
        $this->precargar($fechaInicio, $fechaFin);
        return $this->diasConDiferencia;
    }

    private function asegurarPrecarga(string $fechaInicio, string $fechaFin): void
    {
        if (!$this->rango || $fechaInicio < $this->rango[0] || $fechaFin > $this->rango[1]) {
            $this->precargar($fechaInicio, $fechaFin);
        }
    }

    // ------------------------------------------------------------------ planilla

    private function calcularPlanilla(string $inicio, string $fin): void
    {
        $tarifas = $this->tarifasPlanilla($inicio, $fin);
        $laborPorAsistencia = app(PlanillaAsistenciaLaborConsulta::class)->laborPorAsistencia();
        $descripciones = DB::table('plan_tipo_asistencias')->pluck('descripcion', 'codigo');

        $registros = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->whereBetween('r.fecha', [$inicio, $fin])
            ->get(['r.id', 'r.fecha', 'r.asistencia', 'r.total_horas', 'd.plan_empleado_id', 'd.nombres'])
            ->keyBy('id');
        $detalles = DB::table('plan_detalles_horas as h')
            ->join('plan_registros_diarios as r', 'r.id', '=', 'h.plan_reg_dia_id')
            ->whereBetween('r.fecha', [$inicio, $fin])
            ->get(['h.id', 'h.plan_reg_dia_id', 'h.campo_nombre', 'h.codigo_labor', 'h.hora_inicio', 'h.hora_fin'])
            ->groupBy('plan_reg_dia_id');

        // Riego de trabajadores de planilla: filas del reporte por trabajador y día (todos los campos)
        $riegoPorDia = ReporteDiarioRiego::with('consolidado')->whereBetween('fecha', [$inicio, $fin])->get()
            ->filter(fn($r) => $r->consolidado && $r->consolidado->trabajador_type === PlanEmpleado::class)
            // En FDM las horas acumuladas no son un campo: se quedan en el tramo de riego del registro diario
            ->reject(fn($r) => mb_strtoupper((string) $r->campo) === self::CAMPO_FDM && $r->por_acumulacion)
            ->groupBy(fn($r) => $r->consolidado->trabajador_id . '|' . Carbon::parse($r->fecha)->toDateString());
        $conConsolidadoRiego = ConsolidadoRiego::where('trabajador_type', PlanEmpleado::class)->whereBetween('fecha', [$inicio, $fin])
            ->get(['trabajador_id', 'fecha'])->mapWithKeys(fn($c) => [$c->trabajador_id . '|' . Carbon::parse($c->fecha)->toDateString() => true]);

        foreach ($registros as $r) {
            $fecha = Carbon::parse($r->fecha)->toDateString();
            $empleado = (int) $r->plan_empleado_id;
            $nombre = $r->nombres ?? '-';
            $tarifaInfo = $tarifas[$empleado . '|' . substr($fecha, 0, 7)] ?? null;
            $tarifa = $tarifaInfo['tarifa'] ?? null;
            $obsTarifa = $tarifa === null ? ($tarifaInfo['observacion'] ?? 'Aún no se ha generado la planilla del mes.') : null;
            $costo = fn(float $minutos) => $tarifa !== null ? $tarifa * $minutos / 60 : 0.0;

            $pagados = (float) $r->total_horas * 60;  // minutos pagados del día
            $tramos = ($detalles[$r->id] ?? collect())->map(function ($h) {
                $t = clone $h;
                $t->minutos = CalculoHelper::obtenerDiferenciaMinutos($h->hora_inicio, $h->hora_fin);
                return $t;
            });
            $minDetalle = (float) $tramos->sum(fn($h) => max(0, $h->minutos));

            // Riego del día: con reporte de riego, cuesta lo del reporte en cada campo y no el tramo 81 del registro
            $clave = $empleado . '|' . $fecha;
            $riego = $riegoPorDia[$clave] ?? collect();
            $esTramoRiego = fn($h) => mb_strtoupper((string) $h->campo_nombre) === self::CAMPO_FDM && (string) $h->codigo_labor === self::LABOR_RIEGO;
            $minRiegoPagado = (float) $tramos->filter($esTramoRiego)->sum(fn($h) => max(0, $h->minutos));
            $minRiegoCampo = 0.0;
            $sincronizado = null;
            if ($riego->isNotEmpty()) {
                $sincronizado = (bool) $riego->first()->consolidado->sincronizado;
                foreach ($riego as $rr) {
                    $min = $this->minutosRiego($rr);
                    $minRiegoCampo += $min;
                    $this->agregar($this->planillaPorCampo, $this->fila($fecha, 'planilla', (int) $rr->id, $rr->campo, null,
                        $rr->por_acumulacion ? 'Uso de horas acumuladas (Riego)' : ($rr->tipo_labor ?? 'Riego'), $nombre, $empleado,
                        $min, $costo($min),
                        $this->unir($sincronizado ? null : 'Las horas de riego no coinciden con el registro diario. Verificar.', $obsTarifa)));
                }
                $tramos = $tramos->reject($esTramoRiego);
            }

            foreach ($tramos as $h) {
                $esMarcadorSinReporte = $esTramoRiego($h) && !isset($conConsolidadoRiego[$clave]);
                $this->agregar($this->planillaPorCampo, $this->fila($fecha, 'planilla', (int) $h->id, $h->campo_nombre, $h->codigo_labor,
                    $this->vigencias()->nombre($h->codigo_labor, $fecha), $nombre, $empleado, $h->minutos, $costo(max(0, $h->minutos)),
                    $this->unir($h->minutos < 0 ? "Horas inválidas en el registro ({$nombre}): la hora de fin es anterior a la de inicio." : null,
                        $esMarcadorSinReporte ? 'No tiene reporte de riego para este día. Verificar.' : null,
                        $minDetalle > $pagados ? sprintf('El detalle suma %s h y el día tiene %s h pagadas. Revisar.', $this->h($minDetalle), $this->h($pagados)) : null,
                        $obsTarifa)));
            }

            // Minutos que quedan en la BDD este día frente a los pagados: la diferencia se explica en el arqueo
            $minEnBdd = $minDetalle - ($riego->isNotEmpty() ? $minRiegoPagado : 0) + $minRiegoCampo + max(0, $pagados - $minDetalle);
            if (abs($minEnBdd - $pagados) >= 1) {
                $causas = [];
                $tipo = 'otro';
                if ($riego->isNotEmpty()) {
                    // Riego: lo regado (horas reales en campo, con uso de acumuladas) vs el jornal de riego vs lo pagado
                    $consolidado = $riego->first()->consolidado;
                    $jornal = (float) $consolidado->minutos_jornal;
                    $conPonderadas = $riego->contains(fn($rr) => (float) $rr->horas_ponderadas > 0 || $rr->por_acumulacion);
                    if (!$conPonderadas && $jornal > 0) {
                        $tipo = 'sin_ponderar';
                        $causas[] = sprintf('Reporte de riego sin horas por campo calculadas (jornal de riego %s h): consolidar el riego de ese día.', $this->h($jornal));
                    } else {
                        if (abs($minRiegoCampo - $jornal) >= 1) {
                            $tipo = 'banco';
                            $causas[] = $minRiegoCampo > $jornal
                                ? sprintf('Banco de horas: regó %s h y el jornal de riego es %s h; las %s h de más se pagan cuando se usen.', $this->h($minRiegoCampo), $this->h($jornal), $this->h($minRiegoCampo - $jornal))
                                : sprintf('Riego: el reporte tiene %s h en campo y el jornal de riego %s h.', $this->h($minRiegoCampo), $this->h($jornal));
                        }
                        if (abs($jornal - $minRiegoPagado) >= 1) {
                            $tipo = 'desincronizado';
                            $causas[] = sprintf('Desincronizado: el registro diario paga %s h de riego y el jornal de riego es %s h. Corregir el registro diario o el reporte de riego.', $this->h($minRiegoPagado), $this->h($jornal));
                        }
                    }
                }
                if ($minDetalle > $pagados) {
                    $tipo = $causas ? $tipo : 'detalle';
                    $causas[] = sprintf('El detalle por campo suma %s h y el día tiene %s h pagadas.', $this->h($minDetalle), $this->h($pagados));
                }
                if (!$causas) {
                    $causas[] = sprintf('La BDD tiene %s h y el día %s h pagadas.', $this->h($minEnBdd), $this->h($pagados));
                }
                $this->diasConDiferencia[] = [
                    'plan_empleado_id' => $empleado,
                    'trabajador' => $nombre,
                    'fecha' => $fecha,
                    'horas_pagadas' => round($pagados / 60, 2),
                    'horas_bdd' => round($minEnBdd / 60, 2),
                    'diferencia_horas' => round(($minEnBdd - $pagados) / 60, 2),
                    'costo' => round($costo($minEnBdd) - $costo($pagados), 2),
                    'tipo' => $tipo,
                    'causa' => implode(' ', $causas),
                    'sincronizado' => $sincronizado,
                ];
            }

            // Horas pagadas del día que no tienen detalle por campo → FDM
            $resto = max(0, $pagados - $minDetalle);
            if ($resto >= 1) {
                if ($r->asistencia === 'A' || $r->asistencia === null || $r->asistencia === '') {
                    $codigo = null;
                    $concepto = self::CONCEPTO_SIN_DETALLE;
                    $obs = 'Revisar: el registro diario tiene más horas que su detalle por campo.';
                } else {
                    $labor = $laborPorAsistencia[$r->asistencia] ?? null;
                    $codigo = $labor['codigo'] ?? null;
                    $concepto = $labor['nombre'] ?? (($descripciones[$r->asistencia] ?? $r->asistencia) . " ({$r->asistencia})");
                    $obs = $labor ? null : "El tipo de asistencia {$r->asistencia} no tiene una labor vinculada (Campo → Labores).";
                }
                $this->agregar($this->planillaPorCampo, $this->fila($fecha, 'planilla', (int) $r->id, self::CAMPO_FDM, $codigo, $concepto,
                    $nombre, $empleado, (int) round($resto), $costo($resto), $this->unir($obs, $obsTarifa)));
            }
        }
    }

    /** Minutos de una fila del reporte de riego: jornal ponderado, o las horas exactas si es uso de horas acumuladas. */
    private function minutosRiego($registro): int
    {
        return max(0, $registro->por_acumulacion
            ? Carbon::parse($registro->hora_inicio)->diffInMinutes(Carbon::parse($registro->hora_fin))
            : (int) round(($registro->horas_ponderadas ?? 0) * 60));
    }

    /**
     * Tarifa pagada por hora de cada trabajador y mes: (sueldo proporcional + aportes) / horas del registro diario.
     *
     * @return array<string, array{tarifa: ?float, observacion: ?string}> "empleado|YYYY-MM"
     */
    private function tarifasPlanilla(string $inicio, string $fin): array
    {
        $tarifas = [];
        for ($m = Carbon::parse($inicio)->startOfMonth(); $m->toDateString() <= $fin; $m->addMonth()) {
            $personal = PlanMensualPersonal::with('planMensual')
                ->whereHas('planMensual', fn($q) => $q->where('mes', $m->month)->where('anio', $m->year))->get();
            foreach ($personal as $p) {
                $tarifa = $p->pagado_sueldo_por_hora;
                $tarifas[$p->plan_empleado_id . '|' . $m->format('Y-m')] = [
                    'tarifa' => $tarifa !== null ? (float) $tarifa : null,
                    'observacion' => $tarifa === null ? 'El trabajador no tiene horas en la planilla del mes: su pago no se puede repartir.' : null,
                ];
            }
        }
        return $tarifas;
    }

    // ------------------------------------------------------------------ bono de productividad

    /** Bono de productividad por actividad, en el campo de la actividad (se acumula y se paga aparte). */
    private function calcularBonoProductividad(string $inicio, string $fin): void
    {
        $bonos = DB::table('plan_actividad_bonos as b')
            ->join('actividades as a', 'a.id', '=', 'b.actividad_id')
            ->join('plan_registros_diarios as r', 'r.id', '=', 'b.registro_diario_id')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->leftJoin('labores as l', 'l.id', '=', 'a.labor_id')
            ->whereBetween('r.fecha', [$inicio, $fin])
            ->where('b.total_bono', '>', 0)
            ->get(['b.id', 'b.total_bono', 'r.id as registro_id', 'r.fecha', 'd.nombres', 'd.plan_empleado_id', 'a.campo', 'a.labor_id',
                'a.codigo_labor as actividad_codigo', 'l.codigo as tabla_codigo', 'a.nombre_labor as actividad_nombre', 'l.nombre_labor as tabla_nombre']);
        if ($bonos->isEmpty()) {
            return;
        }
        // Minutos del detalle de ese día en el campo y labor de la actividad (referencia)
        $minutos = DB::table('plan_detalles_horas')->whereIn('plan_reg_dia_id', $bonos->pluck('registro_id')->unique())
            ->get(['plan_reg_dia_id', 'campo_nombre', 'codigo_labor', 'hora_inicio', 'hora_fin'])
            ->groupBy(fn($h) => $h->plan_reg_dia_id . '|' . $h->campo_nombre . '|' . $h->codigo_labor)
            ->map(fn($g) => $g->sum(fn($h) => max(0, CalculoHelper::obtenerDiferenciaMinutos($h->hora_inicio, $h->hora_fin))));

        foreach ($bonos as $b) {
            $codigo = $b->actividad_codigo ?? $b->tabla_codigo ?? (string) $b->labor_id;
            $min = (int) ($minutos[$b->registro_id . '|' . $b->campo . '|' . $codigo] ?? 0);
            $this->agregar($this->bonoProductividadPorCampo, $this->fila(Carbon::parse($b->fecha)->toDateString(), 'planilla_bono_productividad', (int) $b->id,
                $b->campo, $codigo, $b->actividad_nombre ?: ($b->tabla_nombre ?? 'Bono de productividad'), $b->nombres ?? '-',
                (int) $b->plan_empleado_id, $min, (float) $b->total_bono, null, $min > 0 ? round($min / 480, 2) : 0));
        }
    }

    // ------------------------------------------------------------------ cuadrilla

    /**
     * Cuadrilla (sin cambios de criterio): jornal del día / 8 × horas del detalle; riego de cuadrilleros con su
     * jornal; bonos que se pagan con el jornal ('cuadrilla') o aparte ('cuadrilla_bono').
     */
    private function calcularCuadrilla(string $inicio, string $fin): void
    {
        $riegoCuadrilla = ReporteDiarioRiego::with('consolidado.trabajador')->whereBetween('fecha', [$inicio, $fin])->get()
            ->filter(fn($r) => $r->consolidado && $r->consolidado->trabajador_type !== PlanEmpleado::class);
        $conRiego = ConsolidadoRiego::where('trabajador_type', Cuadrillero::class)->whereBetween('fecha', [$inicio, $fin])
            ->get(['trabajador_id', 'fecha'])->mapWithKeys(fn($c) => [$c->trabajador_id . '|' . Carbon::parse($c->fecha)->toDateString() => true]);
        $jornales = DB::table('cuad_registros_diarios')->whereBetween('fecha', [$inicio, $fin])
            ->get(['cuadrillero_id', 'fecha', 'costo_personalizado_dia', 'jornal_aplicado'])
            ->mapWithKeys(fn($r) => [$r->cuadrillero_id . '|' . Carbon::parse($r->fecha)->toDateString() => (float) ($r->costo_personalizado_dia ?: $r->jornal_aplicado)]);

        $detalles = CuadDetalleHora::query()
            ->join('cuad_registros_diarios as r', 'r.id', '=', 'cuad_detalles_horas.registro_diario_id')
            ->whereBetween('r.fecha', [$inicio, $fin])
            ->select('cuad_detalles_horas.*')
            ->with(['registroDiario.cuadrillero', 'registroDiario.actividadesBonos.metodo', 'registroDiario.actividadesBonos.actividad'])
            ->get();

        foreach ($detalles as $detalle) {
            $rd = $detalle->registroDiario;
            $codigoLabor = $detalle->codigo_labor;
            $fecha = Carbon::parse($rd->fecha)->toDateString();

            // Marcador de riego en FDM: si hay reporte de riego, lo costea el riego de cuadrilleros
            if (mb_strtoupper((string) $detalle->campo_nombre) === self::CAMPO_FDM && (string) $codigoLabor === self::LABOR_RIEGO
                && isset($conRiego[$rd->cuadrillero_id . '|' . $fecha])) {
                continue;
            }

            // Labor a destajo = tiene bono con método sin estándar (mismo criterio que el trigger de horas_destajo)
            $esDestajo = $rd->actividadesBonos->contains(fn($b) => $b->metodo_id && $b->metodo && $b->metodo->estandar === null
                && (string) $b->actividad?->codigo_labor === (string) $codigoLabor);
            $minutos = CalculoHelper::obtenerDiferenciaMinutos($detalle->hora_inicio, $detalle->hora_fin);
            $jornal = (float) ($rd->costo_personalizado_dia ?: $rd->jornal_aplicado);

            $this->agregar($this->cuadrillaPorCampo, $this->fila($fecha, 'cuadrilla', (int) $detalle->id, $detalle->campo_nombre, $codigoLabor,
                $this->vigencias()->nombre($codigoLabor, $fecha), $rd->cuadrillero?->nombres ?? '-', null, $minutos,
                $esDestajo ? 0 : $jornal / 8 * (max(0, $minutos) / 60),
                $this->unir(
                    $minutos < 0 ? "Horas inválidas en el registro ({$rd->cuadrillero?->nombres}): la hora de fin es anterior a la de inicio." : null,
                    $esDestajo ? 'A destajo: se paga con su bono.' : null,
                    $jornal <= 0 ? 'Sin jornal definido para el grupo en este día.' : null
                )));
        }

        foreach ($riegoCuadrilla as $registro) {
            $consolidado = $registro->consolidado;
            if (mb_strtoupper((string) $registro->campo) === self::CAMPO_FDM && $registro->por_acumulacion) {
                continue;
            }
            $fecha = Carbon::parse($registro->fecha)->toDateString();
            $minutos = $registro->por_acumulacion
                ? Carbon::parse($registro->hora_inicio)->diffInMinutes(Carbon::parse($registro->hora_fin))
                : (int) round(($registro->horas_ponderadas ?? 0) * 60);
            $jornal = $jornales[$consolidado->trabajador_id . '|' . $fecha] ?? null;
            $this->agregar($this->cuadrillaPorCampo, $this->fila($fecha, 'cuadrilla', (int) $registro->id, $registro->campo, null,
                $registro->por_acumulacion ? 'Uso de horas acumuladas (Riego)' : ($registro->tipo_labor ?? 'Riego'),
                $consolidado->trabajador_nombre, null, $minutos, $jornal !== null ? $jornal * ($minutos / 480) : 0,
                $this->unir($consolidado->sincronizado ? null : 'Las horas de riego no coinciden con el registro diario. Verificar.',
                    $jornal === null ? 'El cuadrillero no tiene registro diario ni jornal para este día.' : null)));
        }

        $bonos = CuadActividadBono::with(['registroDiario.cuadrillero', 'actividad'])
            ->where('total_bono', '>', 0)
            ->whereHas('registroDiario', fn($q) => $q->whereBetween('fecha', [$inicio, $fin]))
            ->get();
        foreach ($bonos as $b) {
            if (!$b->actividad?->campo) {
                continue;
            }
            $this->agregar($this->cuadrillaPorCampo, $this->fila(Carbon::parse($b->registroDiario->fecha)->toDateString(),
                $b->se_paga_con_jornal ? 'cuadrilla' : 'cuadrilla_bono', (int) $b->id, $b->actividad->campo, $b->actividad->codigo_labor,
                'Bono: ' . ($b->actividad->nombre_labor ?? 'actividad') . ($b->se_paga_con_jornal ? ' (con jornal)' : ' (se paga aparte)'),
                $b->registroDiario->cuadrillero?->nombres ?? '-', null, 0, (float) $b->total_bono, null));
        }
    }

    // ------------------------------------------------------------------ apoyo

    private function agregar(array &$porCampo, array $fila): void
    {
        $porCampo[$fila['campo']][] = $fila;
    }

    private function unir(?string ...$partes): ?string
    {
        $texto = collect($partes)->filter()->implode(' ');
        return $texto !== '' ? mb_strimwidth($texto, 0, 255, '…') : null;
    }

    private function h(float $minutos): string
    {
        return rtrim(rtrim(number_format($minutos / 60, 2, '.', ''), '0'), '.');
    }

    private function fila(string $fecha, string $origenTipo, int $origenId, ?string $campo, $labor, ?string $laborNombre, string $trabajador,
        ?int $planEmpleadoId, int $minutos, float $costoTotal, ?string $observacion, ?float $jornales = null): array
    {
        // Tramo con hora de fin anterior a la de inicio (dato mal cargado): sin minutos ni costo, marcado para revisar
        if ($minutos < 0) {
            $minutos = 0;
            $costoTotal = 0;
        }
        return [
            'campania' => null, // la pone generarFilas según la campaña que se consolida
            'fecha' => $fecha,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'campo' => (string) $campo,
            'labor' => $labor !== null && $labor !== '' ? $labor : null,
            'labor_nombre' => $laborNombre,
            'trabajador' => $trabajador,
            'plan_empleado_id' => $planEmpleadoId,
            'minutos' => $minutos,
            'cantidad_jornales' => $jornales ?? round($minutos / 480, 3),
            'costo_total' => $costoTotal,
            'observacion' => $observacion,
        ];
    }
}
