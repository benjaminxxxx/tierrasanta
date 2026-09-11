<?php

namespace App\Services\Planilla;

use App\Models\PlanMensualPersonal;
use App\Models\PlanRegistroDiario;
use App\Models\PlanTipoAsistencia;
use DB;
class ResumenAsistenciaMensualServicio
{
    public function obtenerResumenPorMes(int $mes, int $anio): array
    {
        $criterios = PlanTipoAsistencia::where('activo', true)->pluck('criterio_bono_asistencia', 'codigo');

        $conteos = PlanRegistroDiario::query()
            ->join('plan_mensual_detalles', 'plan_mensual_detalles.id', '=', 'plan_registros_diarios.plan_det_men_id')
            ->join('plan_mensuales', 'plan_mensuales.id', '=', 'plan_mensual_detalles.plan_mensual_id')
            ->where('plan_mensuales.mes', $mes)
            ->where('plan_mensuales.anio', $anio)
            ->select('plan_mensual_detalles.plan_empleado_id', 'plan_registros_diarios.asistencia', DB::raw('COUNT(*) as total'))
            ->groupBy('plan_mensual_detalles.plan_empleado_id', 'plan_registros_diarios.asistencia')
            ->get()
            ->groupBy('plan_empleado_id');

        // Bonificación laboral (productividad) — ya calculada por PlanActividadBono/PlanRegistroDiario.total_bono,
        // acá solo se suma por trabajador en el rango del mes.
        $bonosProductividad = PlanRegistroDiario::query()
            ->join('plan_mensual_detalles', 'plan_mensual_detalles.id', '=', 'plan_registros_diarios.plan_det_men_id')
            ->join('plan_mensuales', 'plan_mensuales.id', '=', 'plan_mensual_detalles.plan_mensual_id')
            ->where('plan_mensuales.mes', $mes)
            ->where('plan_mensuales.anio', $anio)
            ->select('plan_mensual_detalles.plan_empleado_id', DB::raw('SUM(plan_registros_diarios.total_bono) as total'))
            ->groupBy('plan_mensual_detalles.plan_empleado_id')
            ->pluck('total', 'plan_empleado_id');

        return PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->get()
            ->map(function ($persona) use ($conteos, $criterios, $bonosProductividad) {

                $conteoEmpleado = $conteos->get($persona->plan_empleado_id, collect())->pluck('total', 'asistencia');

                $totalAfecta = 0;
                $totalRevisar = 0;
                $partes = [];

                foreach ($conteoEmpleado as $codigo => $cantidad) {
                    if ($cantidad <= 0)
                        continue;

                    $criterio = $criterios[$codigo] ?? 'no_afecta';
                    $estilo = match ($criterio) {
                        'afecta' => 'color:#b91c1c;font-weight:600',
                        'revisar' => 'color:#b45309;font-weight:600',
                        default => 'color:#374151',
                    };

                    $partes[] = "<span style=\"{$estilo}\">{$codigo}: {$cantidad}</span>";

                    if ($criterio === 'afecta')
                        $totalAfecta += $cantidad;
                    if ($criterio === 'revisar')
                        $totalRevisar += $cantidad;
                }

                return [
                    'plan_empleado_id' => $persona->plan_empleado_id,
                    'nombres' => $persona->nombres,
                    'resumen_asistencia' => implode(', ', $partes) ?: '-',
                    'vacaciones_plame' => $persona->plame_0118_rem_vacacional,
                    'vacaciones_neto_pagadas' => $persona->vacaciones_neto_pagadas,
                    'vacaciones_negro' => $persona->vacaciones_negro,
                    'calificable_automatico' => $totalAfecta === 0 && $totalRevisar === 0,
                    'bonificacion_asistencia' => $persona->bonificacion_asistencia,
                    'vacaciones_plame_personalizado' => $persona->vacaciones_plame_personalizado,
                    'bonificacion_laboral' => (float) ($bonosProductividad[$persona->plan_empleado_id] ?? 0),
                ];
            })
            ->toArray();
    }
}