<?php

namespace App\Services\Costos\Consolidacion;

use App\Models\PlanMensualPersonal;
use App\Models\PlanTipoAsistencia;
use App\Models\ResumenCostoDiario;
use App\Services\Planilla\Asistencia\PlanillaAsistenciaLaborConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mano de obra indirecta de planilla: pagos del mes que no dependen de horas (vacaciones pagadas, bono de
 * asistencia). Van a FDM como en la BDD de la empresa.
 *
 * Las horas pagadas sin trabajo en campo (descanso médico, feriado, licencias con goce, horas asistidas sin
 * detalle) ya no van aquí: las genera la planilla en FDM con la labor de su tipo de asistencia
 * (ConsolidarCostoPlanillaServicio), como si se hubiera trabajado en FDM. Así:
 *
 *   planilla (campos + FDM) = sueldo proporcional + aportes pagados, por trabajador
 *   mano de obra indirecta  = vacaciones pagadas + bono de asistencia
 *
 * cuadrePorTrabajador() explica las diferencias de cada trabajador para el arqueo.
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
        $laborPorAsistencia = app(PlanillaAsistenciaLaborConsulta::class)->laborPorAsistencia();

        $personal = PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->get()
            ->keyBy('plan_empleado_id');

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
                    $filas[] = $this->fila($fin, $persona->id, $concepto, $nombre, null, $monto, null, $ahora, $codigoLabor, (int) $persona->plan_empleado_id);
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
     * Cuadre por trabajador: lo pagado (sueldo proporcional + aportes) contra lo que tiene en la BDD como
     * planilla (campos + FDM), con la causa de cada diferencia y cómo se corrige.
     *
     * @return array<int, array{trabajador:string, pagado:float, bdd:float, diferencia:float, horas_pagadas:float,
     *   horas_bdd:float, causas:string[]}> solo los que no cuadran (|dif| >= tolerancia), de mayor a menor
     */
    public function cuadrePorTrabajador(int $anio, int $mes, float $tolerancia = 0.05): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $bdd = ResumenCostoDiario::where('origen_tipo', 'planilla')->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw('plan_empleado_id, SUM(costo_total) as costo, SUM(minutos) as minutos, MIN(created_at) as generado')
            ->groupBy('plan_empleado_id')->get()->keyBy('plan_empleado_id');
        // Días en que la BDD no suma las horas pagadas (riego desincronizado, detalle de más…), desde las fuentes
        $dias = collect((new ConsolidarCostoPlanillaServicio())->diasConDiferencia($inicio, $fin))->groupBy('plan_empleado_id');
        $ultimoCambio = DB::table('plan_registros_diarios as r')->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->whereBetween('r.fecha', [$inicio, $fin])->groupBy('d.plan_empleado_id')
            ->selectRaw('d.plan_empleado_id, MAX(r.updated_at) as modificado')->pluck('modificado', 'plan_empleado_id');

        $resultado = [];
        $personal = PlanMensualPersonal::with('planMensual')->whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))->get();
        foreach ($personal as $p) {
            $pagado = (float) ($p->pagado_sueldo_bruto_negro ?? 0);
            $fila = $bdd->get($p->plan_empleado_id);
            $enBdd = (float) ($fila->costo ?? 0);
            $diferencia = round($enBdd - $pagado, 2);
            if (abs($diferencia) < $tolerancia) {
                continue;
            }
            $causas = [];
            if ((float) $p->plame_total_horas <= 0) {
                $causas[] = 'Se le paga en planilla pero no tiene horas en el registro diario: no hay a qué campo cargar su costo. Registrar sus días (o su suspensión).';
            }
            if (!$fila && (float) $p->plame_total_horas > 0) {
                $causas[] = 'No tiene filas en la BDD: reconstruir la mano de obra del mes.';
            } elseif ($fila && $fila->generado && ($p->updated_at > $fila->generado || ($ultimoCambio[$p->plan_empleado_id] ?? null) > $fila->generado)) {
                $causas[] = 'La planilla o el registro diario cambiaron después de la última consolidación: reconstruir la mano de obra del mes.';
            }
            $explicado = 0.0;
            $porTipo = $dias->get($p->plan_empleado_id, collect())->groupBy('tipo');
            $nombresTipo = [
                'banco' => 'Banco de horas de riego (horas regadas por encima del jornal, aún sin usar)',
                'desincronizado' => 'Riego desincronizado (el registro diario no paga el jornal de riego): corregir',
                'sin_ponderar' => 'Reporte de riego sin horas por campo calculadas: consolidar el riego de esos días',
                'detalle' => 'Detalle por campo con más horas que las pagadas: corregir el registro diario',
                'otro' => 'Otras diferencias de horas',
            ];
            foreach ($nombresTipo as $tipo => $texto) {
                if ($porTipo->has($tipo)) {
                    $g = $porTipo[$tipo];
                    $causas[] = sprintf('RESUMEN · %s: %d día(s), %s h, S/ %s.', $texto, $g->count(),
                        number_format($g->sum('diferencia_horas'), 2), number_format($g->sum('costo'), 2));
                }
            }
            foreach ($dias->get($p->plan_empleado_id, collect())->sortBy('fecha') as $d) {
                $explicado += $d['costo'];
                $causas[] = sprintf('%s: %s %s %s h → S/ %s.%s', Carbon::parse($d['fecha'])->format('d/m'), $d['causa'],
                    $d['diferencia_horas'] > 0 ? 'Sobran' : 'Faltan', number_format(abs($d['diferencia_horas']), 2), number_format($d['costo'], 2),
                    $d['sincronizado'] === false ? ' Corregir el registro diario o el reporte de riego de ese día.' : '');
            }
            if ($dias->has($p->plan_empleado_id) && abs($explicado - $diferencia) >= 0.05 && $fila) {
                $causas[] = sprintf('Los días listados explican S/ %s de S/ %s: el resto, consolidación desactualizada (reconstruir).',
                    number_format($explicado, 2), number_format($diferencia, 2));
            }
            if (!$causas) {
                $causas[] = 'Sin causa detectada: revisar las filas del trabajador en la BDD.';
            }
            $resultado[] = [
                'trabajador' => $p->nombres,
                'pagado' => round($pagado, 2),
                'bdd' => round($enBdd, 2),
                'diferencia' => $diferencia,
                'horas_pagadas' => (float) $p->plame_total_horas,
                'horas_bdd' => ((float) ($fila->minutos ?? 0)) / 60,
                'causas' => $causas,
            ];
        }
        // Filas de planilla sin trabajador vinculado (consolidadas antes de guardar el trabajador)
        $sinTrabajador = $bdd->get('');
        if ($sinTrabajador && (float) $sinTrabajador->costo != 0.0) {
            $resultado[] = [
                'trabajador' => '(filas sin trabajador vinculado)', 'pagado' => 0.0, 'bdd' => round((float) $sinTrabajador->costo, 2),
                'diferencia' => round((float) $sinTrabajador->costo, 2), 'horas_pagadas' => 0.0, 'horas_bdd' => ((float) $sinTrabajador->minutos) / 60,
                'causas' => ['Filas consolidadas con la versión anterior: reconstruir la mano de obra del mes.'],
            ];
        }
        usort($resultado, fn($a, $b) => abs($b['diferencia']) <=> abs($a['diferencia']));

        return $resultado;
    }
    private function fila(string $fecha, int $origenId, string $concepto, string $trabajador, ?float $horas, float $costo, ?string $observacion, $ahora, ?string $codigoLabor = null, ?int $planEmpleadoId = null): array
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
            'plan_empleado_id' => $planEmpleadoId,
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
