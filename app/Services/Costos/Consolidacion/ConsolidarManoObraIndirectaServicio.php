<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\PlanMensualPersonal;
use App\Models\PlanTipoAsistencia;
use App\Models\ResumenCostoDiario;
use App\Services\Planilla\Asistencia\PlanillaAsistenciaLaborConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mano de obra indirecta de planilla: lo que se paga al trabajador sin que haya trabajo en campo.
 *
 * El costo por hora pagado de planilla (pagado_sueldo_por_hora) reparte el costo del mes entre
 * TODAS las horas PLAME (asistidas, feriados, descansos médicos, licencias con goce…), pero solo
 * las horas asistidas tienen detalle por campo. Aquí se registran las demás, más los pagos que
 * no dependen de horas (vacaciones pagadas, bono de asistencia), para que se cumpla:
 *
 *   costo en campo (origen 'planilla') + mano de obra indirecta = total pagado a los trabajadores
 *
 * Todo entra a la BDD como campo FDM (campaña FDM), igual que hacía la macro antigua: un día DM de 8 h
 * es "FDM, 8 h, labor 97 (Descanso médico)"; un feriado, "FDM, labor 199". El código de labor sale de la
 * labor vinculada al tipo de asistencia (labores.tipo_asistencia_codigo). Así las horas de la BDD por
 * trabajador cuadran con las horas PLAME.
 *
 * Los tramos del detalle con labor de suspensión (A + 4 h de 97) ya entran como 'planilla' en FDM.
 */
class ConsolidarManoObraIndirectaServicio
{
    public const ORIGEN = 'mano_obra_indirecta';
    public const CAMPO = 'FDM';
    public const CAMPANIA = 'FDM';
    /** Campaña con la que se guardaba antes de ir a FDM (filas viejas aún no regeneradas). */
    public const CAMPANIA_ANTERIOR = 'MANO DE OBRA INDIRECTA';

    /** Conceptos que no dependen de horas (columnas de plan_mensual_personals). */
    public const CONCEPTOS_MONTO = [
        'vacaciones_neto_pagadas' => 'Vacaciones pagadas',
        'bonificacion_asistencia' => 'Bonificación 100% asistencia',
    ];

    public const CONCEPTO_SIN_DETALLE = 'Horas asistidas sin detalle de campo';

    /**
     * Regenera las filas del mes. Devuelve la cantidad de filas y avisos para el cuadre.
     *
     * @return array{filas:int, avisos:string[]}
     */
    public function consolidarMes(int $anio, int $mes): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $filas = [];
        $avisos = [];
        $ahora = now();
        $descripciones = PlanTipoAsistencia::pluck('descripcion', 'codigo');
        $laborPorAsistencia = app(PlanillaAsistenciaLaborConsulta::class)->laborPorAsistencia();

        $personal = PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->get()
            ->keyBy('plan_empleado_id');

        // Registros diarios del mes con horas: los no asistidos completos y los asistidos con
        // horas que no llegan al detalle por campo
        $registros = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->join('plan_mensuales as m', 'm.id', '=', 'd.plan_mensual_id')
            ->where('m.mes', $mes)->where('m.anio', $anio)
            ->where('r.total_horas', '>', 0)
            ->leftJoinSub(
                DB::table('plan_detalles_horas')
                    ->selectRaw('plan_reg_dia_id, SUM(TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin)) as minutos_detalle')
                    ->groupBy('plan_reg_dia_id'),
                'dh',
                'dh.plan_reg_dia_id',
                '=',
                'r.id'
            )
            ->get(['r.id', 'r.fecha', 'r.asistencia', 'r.total_horas', 'd.plan_empleado_id', 'd.nombres', 'dh.minutos_detalle']);

        foreach ($registros as $r) {
            $persona = $personal->get($r->plan_empleado_id);
            $tarifa = $persona?->pagado_sueldo_por_hora;

            $codigoLabor = null;
            if ($r->asistencia === 'A') {
                $horas = (float) $r->total_horas - ((float) ($r->minutos_detalle ?? 0)) / 60;
                if ($horas < 0.01) {
                    continue; // el detalle cubre el día: todo ya está en campo
                }
                $concepto = self::CONCEPTO_SIN_DETALLE;
                $observacion = 'Revisar: el registro diario tiene más horas que su detalle por campo.';
            } else {
                $horas = (float) $r->total_horas;
                $labor = $laborPorAsistencia[$r->asistencia] ?? null;
                $codigoLabor = $labor['codigo'] ?? null;
                $concepto = $labor['nombre'] ?? (($descripciones[$r->asistencia] ?? $r->asistencia) . " ({$r->asistencia})");
                $observacion = $labor ? null
                    : "El tipo de asistencia {$r->asistencia} no tiene una labor vinculada (Campo → Labores).";
            }

            if ($tarifa === null) {
                $observacion = trim(($observacion ?? '') . ' Aún no se ha generado la planilla del mes.');
            }

            $filas[] = $this->fila(
                Carbon::parse($r->fecha)->toDateString(),
                $r->id,
                $concepto,
                // Mismo nombre que las filas de campo (registro diario): en planilla puede estar sin tildes
                $r->nombres ?? $persona?->nombres,
                $horas,
                $tarifa !== null ? (float) $tarifa * $horas : 0.0,
                $observacion,
                $ahora,
                $codigoLabor
            );
        }

        // Pagos del mes que no dependen de horas (las vacaciones pagadas llevan la labor de vacaciones)
        $nombresRegistro = DB::table('plan_mensual_detalles as d')
            ->join('plan_mensuales as m', 'm.id', '=', 'd.plan_mensual_id')
            ->where('m.mes', $mes)->where('m.anio', $anio)
            ->pluck('d.nombres', 'd.plan_empleado_id');
        foreach ($personal as $persona) {
            foreach (self::CONCEPTOS_MONTO as $columna => $concepto) {
                $monto = (float) ($persona->{$columna} ?? 0);
                if ($monto > 0) {
                    $codigoLabor = $columna === 'vacaciones_neto_pagadas' ? ($laborPorAsistencia['V']['codigo'] ?? null) : null;
                    $nombre = $nombresRegistro[$persona->plan_empleado_id] ?? $persona->nombres;
                    $filas[] = $this->fila($fin, $persona->id, $concepto, $nombre, null, $monto, null, $ahora, $codigoLabor);
                }
            }
        }

        // Vacaciones registradas (V) sin monto pagado: su costo no entra a ningún lado
        $diasV = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->join('plan_mensuales as m', 'm.id', '=', 'd.plan_mensual_id')
            ->where('m.mes', $mes)->where('m.anio', $anio)
            ->where('r.asistencia', 'V')
            ->selectRaw('d.plan_empleado_id, d.nombres, COUNT(*) as dias')
            ->groupBy('d.plan_empleado_id', 'd.nombres')
            ->get();
        foreach ($diasV as $v) {
            if ((float) ($personal->get($v->plan_empleado_id)?->vacaciones_neto_pagadas ?? 0) <= 0) {
                $avisos[] = "{$v->nombres}: {$v->dias} día(s) de vacaciones sin monto de vacaciones pagadas registrado.";
            }
        }

        DB::transaction(function () use ($inicio, $fin, $filas) {
            ResumenCostoDiario::where('origen_tipo', self::ORIGEN)->whereBetween('fecha', [$inicio, $fin])->delete();
            foreach (array_chunk($filas, 500) as $chunk) {
                ResumenCostoDiario::insert($chunk);
            }
        });

        return ['filas' => count($filas), 'avisos' => $avisos];
    }

    /**
     * Total pagado a los trabajadores de planilla en el mes:
     * sueldo proporcional + aportes (pagado_sueldo_bruto_negro) + vacaciones pagadas + bono de asistencia.
     *
     * @return array{sueldo_aportes:float, vacaciones_neto_pagadas:float, bonificacion_asistencia:float, total:float}
     */
    public function totalPagado(int $anio, int $mes): array
    {
        $personal = PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))->get();

        $sueldo = (float) $personal->sum(fn($p) => (float) ($p->pagado_sueldo_bruto_negro ?? 0));
        $vacaciones = (float) $personal->sum(fn($p) => (float) ($p->vacaciones_neto_pagadas ?? 0));
        $bono = (float) $personal->sum(fn($p) => (float) ($p->bonificacion_asistencia ?? 0));

        return [
            'sueldo_aportes' => $sueldo,
            'vacaciones_neto_pagadas' => $vacaciones,
            'bonificacion_asistencia' => $bono,
            'total' => $sueldo + $vacaciones + $bono,
        ];
    }

    /**
     * Mano de obra indirecta del mes por concepto (desde la BDD).
     *
     * @return array<string, array{horas:float, costo:float}>
     */
    public function totalesPorConcepto(string $fechaInicio, string $fechaFin): array
    {
        return ResumenCostoDiario::where('origen_tipo', self::ORIGEN)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->selectRaw('labor_nombre, SUM(minutos) as minutos, SUM(costo_total) as costo')
            ->groupBy('labor_nombre')
            ->orderByDesc('costo')
            ->get()
            ->mapWithKeys(fn($r) => [$r->labor_nombre => ['horas' => ((float) $r->minutos) / 60, 'costo' => (float) $r->costo]])
            ->all();
    }

    /**
     * Trabajadores cuyo costo en campo no es tarifa pagada × horas asistidas del registro diario.
     * Pasa sobre todo con regadores: su costo en campo sale del reporte de riego (horas ponderadas
     * y horas acumuladas), que puede registrar más horas de las que se pagan en planilla.
     *
     * @return array<int, array{trabajador:string, horas_campo:float, horas_asistidas:float, costo_campo:float, costo_esperado:float, diferencia:float}>
     */
    public function diferenciasPorTrabajador(int $anio, int $mes, float $tolerancia = 0.5): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
        $normalizar = fn(?string $n) => preg_replace('/\s+/', ' ', mb_strtoupper(trim(\Illuminate\Support\Str::ascii((string) $n))));

        $horasAsistidas = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->join('plan_mensuales as m', 'm.id', '=', 'd.plan_mensual_id')
            ->where('m.mes', $mes)->where('m.anio', $anio)->where('r.asistencia', 'A')
            ->groupBy('d.plan_empleado_id')
            ->selectRaw('d.plan_empleado_id, SUM(r.total_horas) as horas')
            ->pluck('horas', 'plan_empleado_id');

        $campo = ResumenCostoDiario::where('origen_tipo', 'planilla')
            ->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw('trabajador, SUM(costo_total) as costo, SUM(minutos) as minutos')
            ->groupBy('trabajador')
            ->get()
            ->groupBy(fn($r) => $normalizar($r->trabajador))
            ->map(fn($g) => ['costo' => (float) $g->sum('costo'), 'horas' => ((float) $g->sum('minutos')) / 60]);

        $resultado = [];
        $personal = PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))->get();
        foreach ($personal as $p) {
            $horas = (float) ($horasAsistidas[$p->plan_empleado_id] ?? 0);
            $esperado = (float) ($p->pagado_sueldo_por_hora ?? 0) * $horas;
            $enCampo = $campo->get($normalizar($p->nombres), ['costo' => 0.0, 'horas' => 0.0]);
            $diferencia = $enCampo['costo'] - $esperado;
            if (abs($diferencia) >= $tolerancia) {
                $resultado[] = [
                    'trabajador' => $p->nombres,
                    'horas_campo' => $enCampo['horas'],
                    'horas_asistidas' => $horas,
                    'costo_campo' => $enCampo['costo'],
                    'costo_esperado' => $esperado,
                    'diferencia' => $diferencia,
                ];
            }
        }
        usort($resultado, fn($a, $b) => abs($b['diferencia']) <=> abs($a['diferencia']));

        return $resultado;
    }

    private function fila(string $fecha, int $origenId, string $concepto, string $trabajador, ?float $horas, float $costo, ?string $observacion, $ahora, ?string $codigoLabor = null): array
    {
        return [
            'campania' => self::CAMPANIA,
            'fecha' => $fecha,
            'origen_tipo' => self::ORIGEN,
            'origen_id' => $origenId,
            'campo' => self::CAMPO,
            'labor' => $codigoLabor,
            'labor_nombre' => $concepto,
            'trabajador' => $trabajador,
            'cuadrilla_grupo_id' => null,
            'tipo_cambio' => 1.0000,
            'minutos' => $horas !== null ? (int) round($horas * 60) : null,
            'cantidad_jornales' => $horas !== null ? round($horas / 8, 4) : null,
            'insumo_nombre' => null,
            'orden_compra' => null,
            'factura' => null,
            'tienda_comercial' => null,
            'cantidad_insumo' => null,
            'costo_total' => $costo,
            'observacion' => $observacion,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
    }
}
