<?php

namespace App\Services\Planilla\Asistencia;

use App\Models\ConsolidadoRiego;
use App\Models\PlanEmpleado;
use App\Models\PlanGrupo;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Models\PlanMensualPersonal;
use App\Models\PlanTipoAsistencia;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use Illuminate\Support\Carbon;

/**
 * Asistencia mensual de la planilla agraria: por empleado, las horas y el tipo de asistencia de cada día,
 * lo que se le paga y lo que le cuesta a la empresa.
 *
 * - Horas y tipo: registro diario (plan_registros_diarios).
 * - Sueldos: la planilla generada del mes (plan_mensual_personals). Sin planilla generada no hay sueldos.
 *     pagado = sueldo_pagado: lo que se calcula para pagarle al trabajador.
 *     costo  = pagado_sueldo_bruto_negro: lo pagado + sus aportes + los aportes del empleador.
 *   El monto de un día es el del mes repartido por sus horas (lo calcula la pantalla).
 * - Grupo: el del contrato vigente en el mes.
 * - Riego: horas del consolidado de riego (reg_resumen) y si están sincronizadas con el registro diario.
 */
class PlanillaAsistenciaMensualConsulta
{
    /**
     * @param array $orden criterios de orden guardados ([['campo' => ..., 'direccion' => ...]])
     * @return array{dias: array, empleados: array, tipos: array, grupos: array, planilla_generada: bool}
     */
    public function obtener(int $mes, int $anio, array $orden = []): array
    {
        $dias = $this->dias($mes, $anio);
        $tipos = PlanTipoAsistencia::get(['codigo', 'descripcion', 'color', 'tipo'])
            ->mapWithKeys(fn($t) => [$t->codigo => [
                'descripcion' => ucfirst(mb_strtolower($t->descripcion)),
                'color' => $this->esBlanco($t->color) ? null : $t->color,
                'tipo' => $t->tipo,
            ]])->all();

        $planMensual = PlanMensual::where('mes', $mes)->where('anio', $anio)->first();
        $detalles = $planMensual
            ? PlanMensualDetalle::where('plan_mensual_id', $planMensual->id)->with('registrosDiarios')->get()->keyBy('plan_empleado_id')
            : collect();
        $personal = $planMensual
            ? PlanMensualPersonal::where('plan_mensual_id', $planMensual->id)->get()
                ->each(fn($p) => $p->setRelation('planMensual', $planMensual)) // los accessors de sueldo leen las horas del mes
                ->keyBy('plan_empleado_id')
            : collect();
        $riego = $this->riego($mes, $anio);
        $coloresGrupo = PlanGrupo::pluck('color', 'codigo');

        $empleados = app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($mes, $anio, $orden)
            ->map(function (PlanEmpleado $empleado) use ($detalles, $personal, $riego, $coloresGrupo) {
                $porDia = [];
                $totalHoras = 0.0;
                foreach ($detalles->get($empleado->id)?->registrosDiarios ?? [] as $registro) {
                    $dia = Carbon::parse($registro->fecha)->day;
                    $horas = (float) $registro->total_horas;
                    $totalHoras += $horas;
                    $porDia[$dia] = ['h' => $horas, 't' => $registro->asistencia];
                }
                // Riego: aunque ese día no tenga registro diario (entonces en planilla son 0 horas)
                foreach ($riego[$empleado->id] ?? [] as $dia => $r) {
                    $porDia[$dia] = ($porDia[$dia] ?? ['h' => 0.0, 't' => null]) + ['rh' => $r['horas'], 'ra' => !$r['sincronizado']];
                }

                $planilla = $personal->get($empleado->id);
                $grupo = $empleado->contratos->first()?->grupo_codigo;

                return [
                    'id' => $empleado->id,
                    'nombre' => mb_strtoupper($empleado->nombre_completo ?? ''),
                    'documento' => $empleado->documento,
                    'grupo' => $grupo,
                    'grupo_color' => $grupo ? ($coloresGrupo[$grupo] ?? null) : null,
                    'dias' => $porDia,
                    'total_horas' => round($totalHoras, 2),
                    'pagado' => $planilla ? round((float) $planilla->sueldo_pagado, 2) : null,
                    'costo' => $planilla && $planilla->pagado_sueldo_bruto_negro !== null ? round((float) $planilla->pagado_sueldo_bruto_negro, 2) : null,
                    // Las horas cambiaron después de generar la planilla: sus sueldos ya no corresponden
                    'desactualizado' => $planilla !== null && abs((float) $planilla->plame_total_horas - $totalHoras) >= 0.01,
                    'alertas_riego' => count(array_filter($porDia, fn($d) => !empty($d['ra']))),
                ];
            })->values()->all();

        return [
            'dias' => $dias,
            'empleados' => $empleados,
            'tipos' => $tipos,
            'grupos' => collect($empleados)->pluck('grupo')->filter()->unique()->sort()->values()->all(),
            'planilla_generada' => $personal->isNotEmpty(),
        ];
    }

    /** @return array<int, array{dia:int, letra:string, fecha:string, domingo:bool}> */
    private function dias(int $mes, int $anio): array
    {
        $letras = [1 => 'L', 'M', 'M', 'J', 'V', 'S', 'D'];
        $dias = [];
        $fecha = Carbon::create($anio, $mes, 1);
        for ($d = 1, $ultimo = $fecha->daysInMonth; $d <= $ultimo; $d++, $fecha->addDay()) {
            $dias[] = ['dia' => $d, 'letra' => $letras[$fecha->dayOfWeekIso], 'fecha' => $fecha->toDateString(), 'domingo' => $fecha->isSunday()];
        }
        return $dias;
    }

    /** @return array<int, array<int, array{horas: float, sincronizado: bool}>> plan_empleado_id => día => riego */
    private function riego(int $mes, int $anio): array
    {
        $mapa = [];
        ConsolidadoRiego::where('trabajador_type', PlanEmpleado::class)->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
            ->get(['trabajador_id', 'fecha', 'minutos_jornal', 'sincronizado'])
            ->each(function ($r) use (&$mapa) {
                $dia = Carbon::parse($r->fecha)->day;
                $previo = $mapa[$r->trabajador_id][$dia] ?? ['horas' => 0.0, 'sincronizado' => true];
                $mapa[$r->trabajador_id][$dia] = [
                    'horas' => round($previo['horas'] + $r->minutos_jornal / 60, 2),
                    'sincronizado' => $previo['sincronizado'] && (bool) $r->sincronizado,
                ];
            });
        return $mapa;
    }

    private function esBlanco(?string $color): bool
    {
        return !$color || in_array(strtoupper($color), ['#FFFFFF', '#FFF'], true);
    }
}
