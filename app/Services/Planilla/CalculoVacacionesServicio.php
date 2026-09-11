<?php

namespace App\Services\Planilla;

use App\Models\PlanEmpleado;
use App\Models\PlanMensualPersonal;
use App\Models\PlanSuspension;
use Illuminate\Support\Carbon;

class CalculoVacacionesServicio
{
    private const CODIGO_SUSPENSION_VACACIONES = '23'; // S.I. Descanso vacacional

    /**
     * Lista los trabajadores con una suspensión de tipo "vacaciones" que
     * se traslapa con el mes/año consultado, con los datos necesarios
     * para el cálculo (aún sin calcular el monto).
     */
    public function obtenerSuspensionesVacacionalesDelMes(int $mes, int $anio): array
    {
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth();

        $suspensiones = PlanSuspension::query()
            ->join('plan_tipos_suspension', 'plan_tipos_suspension.id', '=', 'plan_suspensiones.tipo_suspension_id')
            ->where('plan_tipos_suspension.codigo', self::CODIGO_SUSPENSION_VACACIONES)
            ->where('plan_suspensiones.fecha_inicio', '<=', $finMes)
            ->where(function ($q) use ($inicioMes) {
                $q->whereNull('plan_suspensiones.fecha_fin')->orWhere('plan_suspensiones.fecha_fin', '>=', $inicioMes);
            })
            ->select('plan_suspensiones.*')
            ->with('empleado') // ⚠️ ajustar nombre real de la relación en PlanSuspension hacia PlanEmpleado
            ->get();

        $persona = PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->get()
            ->keyBy('plan_empleado_id');

        return $suspensiones->map(function ($suspension) use ($persona) {
            $personaMes = $persona->get($suspension->plan_empleado_id);
           
            return [
                'plan_empleado_id' => $suspension->plan_empleado_id,
                'nombres' => $suspension->empleado->persona->nombre_completo ?? '-', // ⚠️ ajustar según tu accessor real
                'fecha_inicio' => $suspension->fecha_inicio,
                'fecha_fin' => $suspension->fecha_fin,
                'dias_habiles' => $this->contarDiasHabiles(
                    Carbon::parse($suspension->fecha_inicio),
                    Carbon::parse($suspension->fecha_fin ?? now())
                ),
                'pago_jornal_diario' => (float) ($personaMes?->pago_jornal_diario ?? 0),
                'vacaciones_neto_pagadas' => (float) ($personaMes?->vacaciones_neto_pagadas ?? 0),
            ];
        })->toArray();
    }

    /**
     * Cuenta los días del rango excluyendo solo domingo
     * (sábado sí cuenta, como el resto del sistema).
     */
    public function contarDiasHabiles(Carbon $inicio, Carbon $fin): int
    {
        $dias = 0;
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            if (!$cursor->isSunday()) {
                $dias++;
            }
            $cursor->addDay();
        }

        return $dias;
    }

    public function calcularMonto(float $jornalDiarioProyectado, int $diasHabiles): float
    {
        return round($jornalDiarioProyectado * $diasHabiles, 2);
    }

    /**
     * Guarda los montos finales (ya calculados/editados en el modal) y
     * deriva vacaciones_negro = neto pagado - lo que PLAME ya reconoce
     * (usa el personalizado si existe, si no el calculado de PLAME).
     */
    public function guardarCalculos(int $mes, int $anio, array $filas): void
    {
        foreach ($filas as $fila) {
            $personaMes = PlanMensualPersonal::where('plan_empleado_id', $fila['plan_empleado_id'])
                ->whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
                ->first();

            if (!$personaMes) {
                continue;
            }

            $vacacionesPlameEfectivo = $personaMes->vacaciones_plame_personalizado
                ?? $personaMes->plame_0118_rem_vacacional
                ?? 0;

            $netoPagado = (float) ($fila['vacaciones_neto_pagadas'] ?? 0);

            $personaMes->update([
                'vacaciones_neto_pagadas' => $netoPagado,
                'vacaciones_negro' => round($netoPagado - $vacacionesPlameEfectivo, 2),
            ]);
        }
    }
}