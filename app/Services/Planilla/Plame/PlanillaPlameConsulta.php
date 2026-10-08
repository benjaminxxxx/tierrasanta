<?php

namespace App\Services\Planilla\Plame;

use App\Models\PlanContrato;
use App\Models\PlanMensualPersonal;
use App\Models\PlanTipoSuspension;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos del PLAME del mes (plan_mensual_personals), en vivo, para la tabla y para la ficha "Ver PLAME"
 * (equivalente a la boleta R08 de SUNAT, para comparar contra el PLAME real). Solo lectura.
 */
class PlanillaPlameConsulta
{
    /**
     * Trabajadores del PLAME del mes, filtrados por nombre o DNI.
     *
     * @return Collection<int, PlanMensualPersonal> con ->documento
     */
    public function listar(int $mes, int $anio, ?string $busqueda = null): Collection
    {
        $planilla = PlanMensualPersonal::with(['planMensual', 'planEmpleado:id,documento'])
            ->whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->orderBy('orden')
            ->get()
            ->each(fn($p) => $p->documento = $p->planEmpleado?->documento);

        $busqueda = trim((string) $busqueda);
        if ($busqueda === '') {
            return $planilla;
        }

        $termino = mb_strtolower($busqueda);
        return $planilla->filter(fn($p) => str_contains(mb_strtolower((string) $p->nombres), $termino)
            || str_contains((string) $p->documento, $termino))->values();
    }

    /** Ficha del trabajador en el PLAME del mes. */
    public function ficha(int $personalId): array
    {
        $p = PlanMensualPersonal::with(['planMensual', 'planEmpleado'])->findOrFail($personalId);
        $plan = $p->planMensual;
        $empleado = $p->planEmpleado;
        $inicioMes = Carbon::create($plan->anio, $plan->mes, 1);
        $finMes = $inicioMes->copy()->endOfMonth();

        $contrato = PlanContrato::with(['descuento', 'grupo'])
            ->where('plan_empleado_id', $p->plan_empleado_id)
            ->whereDate('fecha_inicio', '<=', $finMes)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicioMes))
            ->orderByDesc('fecha_inicio')
            ->first();

        $ingresos = $this->conceptos($p, PlanillaPlameReglas::INGRESOS);
        $descuentos = $this->conceptos($p, PlanillaPlameReglas::DESCUENTOS);
        $aportes = $this->conceptos($p, PlanillaPlameReglas::APORTES_EMPLEADOR);
        $totalIngresos = round(array_sum(array_column($ingresos, 'monto')), 2);
        $totalDescuentos = round(array_sum(array_column($descuentos, 'monto')), 2);
        $totalAportes = round(array_sum(array_column($aportes, 'monto')), 2);

        $descripciones = PlanTipoSuspension::pluck('descripcion', 'codigo');
        $suspensiones = [];
        foreach (PlanillaPlameReglas::SUSPENSIONES as $codigo => $columna) {
            $dias = (int) $p->{$columna};
            if ($dias > 0) {
                $suspensiones[] = [
                    // Las claves '20'…'27' llegan como int (PHP convierte claves numéricas)
                    'codigo' => (string) $codigo,
                    'motivo' => $descripciones[(string) $codigo] ?? "Código {$codigo}",
                    'dias' => $dias,
                ];
            }
        }
        $diasSubsidiados = array_sum(array_map(
            fn($s) => in_array($s['codigo'], PlanillaPlameReglas::CODIGOS_SUBSIDIADOS, true) ? $s['dias'] : 0,
            $suspensiones,
        ));

        $diasLaborados = (int) $p->plame_dias_laborados;
        $diasNoLaborados = (int) $p->plame_dias_no_laborados;
        $netoCalculado = round($totalIngresos - $totalDescuentos, 2);
        $netoGuardado = round((float) $p->plame_neto_a_pagar, 2);
        $costoFormal = round($totalIngresos + $totalAportes, 2);
        $costoReal = $p->pagado_sueldo_bruto_negro !== null ? round((float) $p->pagado_sueldo_bruto_negro, 2) : null;

        return [
            'periodo' => $inicioMes->translatedFormat('F Y'),
            'periodo_corto' => $inicioMes->format('m/Y'),
            'trabajador' => [
                'documento' => $empleado?->documento,
                'nombres' => $p->nombres,
                // El PLAME usa como fecha de ingreso el inicio del contrato
                'fecha_ingreso' => $contrato?->fecha_inicio ?? $empleado?->fecha_ingreso,
                'fecha_ingreso_empleado' => $empleado?->fecha_ingreso,
                'tipo_contrato' => $contrato?->tipo_contrato,
                'tipo_planilla' => $contrato?->tipo_planilla,
                'regimen' => $contrato?->descuento?->descripcion ?? $p->sistema_pension,
                'codigo_regimen' => $p->sistema_pension,
                'grupo' => $contrato?->grupo?->descripcion ?? $p->grupo,
                'pensionista' => (bool) $p->es_pensionista,
                'edad' => $p->edad,
                'estado_contrato' => $contrato?->estado,
            ],
            'dias' => [
                'laborados' => $diasLaborados,
                'no_laborados' => $diasNoLaborados,
                'subsidiados' => $diasSubsidiados,
                'total_horas' => (float) ($p->plame_horas_jornada ?? $p->plame_total_horas),
                'horas_registradas' => (float) $p->plame_total_horas,
                'horas_meta_mes' => (float) $plan->total_horas,
                'dias_del_mes' => $inicioMes->daysInMonth,
            ],
            'suspensiones' => $suspensiones,
            'ingresos' => $ingresos,
            'descuentos' => $descuentos,
            'aportes' => $aportes,
            'totales' => [
                'ingresos' => $totalIngresos,
                'remuneracion_bruta' => round((float) $p->plame_remuneracion_bruta, 2),
                'descuentos' => $totalDescuentos,
                'neto' => $netoGuardado,
                'aportes_empleador' => $totalAportes,
                'costo_formal' => $costoFormal,
            ],
            'vacaciones' => [
                'dias' => (int) $p->si_23,
                'remuneracion' => round((float) $p->plame_0118_rem_vacacional, 2),
                'remuneracion_legal' => $p->calculadoPlame('0118') ?? round((float) $p->plame_0118_rem_vacacional, 2),
                'compensacion' => round((float) $p->plame_0117_comp_vacacional, 2),
                'plame_personalizado' => $p->ajustePlame('0118')['monto'] ?? null,
                'neto_pagadas' => $p->vacaciones_neto_pagadas,
                'negro' => $p->vacaciones_negro,
            ],
            'costos' => [
                'sueldo_acordado' => $p->proyectado_sueldo_neto_total !== null ? (float) $p->proyectado_sueldo_neto_total : null,
                'sueldo_pagado' => $p->sueldo_pagado !== null ? round((float) $p->sueldo_pagado, 2) : null,
                'costo_real' => $costoReal,
                'costo_hora' => $p->pagado_sueldo_por_hora !== null ? round((float) $p->pagado_sueldo_por_hora, 4) : null,
                'diferencia_sobre_plame' => $costoReal !== null ? round($costoReal - $costoFormal, 2) : null,
                'bono_productividad' => round((float) $p->bono_productividad, 2),
                'bonificacion_asistencia' => $p->bonificacion_asistencia,
            ],
            'verificaciones' => $this->verificaciones($diasLaborados, $diasNoLaborados, $inicioMes->daysInMonth,
                $suspensiones, $netoCalculado, $netoGuardado, $p, $contrato),
        ];
    }

    /** @return array<int, array{codigo: string, concepto: string, monto: float}> solo los conceptos con monto */
    private function conceptos(PlanMensualPersonal $p, array $catalogo): array
    {
        $resultado = [];
        foreach ($catalogo as $codigo => [$columna, $concepto]) {
            $monto = round((float) $p->{$columna}, 2);
            if ($monto != 0.0 || $p->ajustePlame((string) $codigo)) {
                $ajuste = $p->ajustePlame((string) $codigo);
                $resultado[] = ['codigo' => $codigo, 'concepto' => $concepto, 'monto' => $monto,
                    // Ajuste manual: lo calculado por el sistema y el motivo
                    'calculado' => $ajuste ? $p->calculadoPlame((string) $codigo) : null, 'motivo' => $ajuste['motivo'] ?? null, 'ajustado' => (bool) $ajuste];
            }
        }
        return $resultado;
    }

    /**
     * Comprobaciones de coherencia para contrastar con el PLAME real.
     *
     * @return array<int, array{ok: bool, texto: string}>
     */
    private function verificaciones(int $laborados, int $noLaborados, int $diasMes, array $suspensiones,
        float $netoCalculado, float $netoGuardado, PlanMensualPersonal $p, ?PlanContrato $contrato): array
    {
        $diasSuspension = array_sum(array_column($suspensiones, 'dias'));
        $verificaciones = [
            [
                'ok' => $laborados + $noLaborados === $diasMes,
                'texto' => "Días laborados + no laborados = {$laborados} + {$noLaborados} = " . ($laborados + $noLaborados)
                    . " (el mes tiene {$diasMes} días).",
            ],
            [
                'ok' => abs($netoCalculado - $netoGuardado) < 0.02,
                'texto' => 'Neto a pagar: ingresos − descuentos = S/ ' . number_format($netoCalculado, 2)
                    . '; guardado: S/ ' . number_format($netoGuardado, 2) . '.',
            ],
            [
                'ok' => $diasSuspension <= $noLaborados,
                'texto' => "Días en motivos de suspensión: {$diasSuspension}; días no laborados: {$noLaborados}.",
            ],
        ];

        if (!$contrato) {
            $verificaciones[] = ['ok' => false, 'texto' => 'No hay un contrato vigente en el mes para este trabajador.'];
        } else {
            if ($contrato->plan_sp_codigo !== $p->sistema_pension) {
                $verificaciones[] = [
                    'ok' => false,
                    'texto' => "El sistema de pensión del PLAME ({$p->sistema_pension}) no coincide con el del contrato ({$contrato->plan_sp_codigo}): se cambió después de generar la planilla.",
                ];
            }
            $ingresoEmpleado = $p->planEmpleado?->fecha_ingreso;
            if ($ingresoEmpleado && Carbon::parse($ingresoEmpleado)->toDateString() !== Carbon::parse($contrato->fecha_inicio)->toDateString()) {
                $verificaciones[] = [
                    'ok' => false,
                    'texto' => 'La fecha de ingreso del empleado (' . formatear_fecha($ingresoEmpleado) . ') no coincide con el inicio del contrato ('
                        . formatear_fecha($contrato->fecha_inicio) . '), que es la que usa el PLAME.',
                ];
            }
        }

        // El costo real (base del costo por hora) debería ser al menos el costo formal del PLAME
        $costoReal = $p->pagado_sueldo_bruto_negro;
        $costoFormal = (float) $p->plame_remuneracion_bruta + (float) $p->plame_0312_bonif_ext_temp + (float) $p->plame_0314_beta_30
            + (float) $p->plame_0406_gratif_fiestas_navidad + (float) $p->plame_0904_cts + (float) $p->aportes_empleador;
        if ($costoReal !== null && $costoReal + 0.01 < $costoFormal) {
            $verificaciones[] = [
                'ok' => false,
                'texto' => 'El costo real calculado (S/ ' . number_format($costoReal, 2) . ') es menor que el costo formal del PLAME (S/ '
                    . number_format($costoFormal, 2) . '). El sueldo pagado se prorratea por horas trabajadas y no cuenta los días de '
                    . 'vacaciones ni otras suspensiones pagadas, así que el costo por hora de este mes queda por debajo de lo real.',
            ];
        }

        return $verificaciones;
    }
}
