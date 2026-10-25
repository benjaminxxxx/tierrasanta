<?php

namespace App\Services\Planilla;

use App\Exceptions\ErrorControlado;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Services\Planilla\Parametro\PlanillaParametroConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Mes de planilla. Las tasas del mes ya no se copian a plan_mensuales: se leen de Parámetros de planilla
 * (PlanillaParametroConsulta), así no hay dos versiones del mismo dato.
 */
class PlanillaMensualServicio
{
    /** Sueldo base del mes: RMV / 30 × días del mes. */
    public static function obtenerSueldoBase(int $mes, int $anio): float
    {
        $rmv = PlanillaParametroConsulta::delMes($mes, $anio)['rmv'];
        if ($rmv === null) {
            throw new ErrorControlado(sprintf('No hay RMV vigente en %02d/%04d: regístrala en Planilla → Parámetros de planilla.', $mes, $anio));
        }
        return round(($rmv / 30) * Carbon::create($anio, $mes, 1)->daysInMonth, 2);
    }

    /** El mes de planilla (lo crea si no existe). */
    public static function mes(int $mes, int $anio): PlanMensual
    {
        return PlanMensual::firstOrCreate(['mes' => $mes, 'anio' => $anio]);
    }

    /** Solo el orden: quién está en la lista lo mantiene PlanillaListaMensualProceso (no se agrega ni quita a nadie aquí). */
    public function guardarOrdenMensualEmpleados($mes, $anio, $listaPlanilla)
    {
        app(\App\Services\Planilla\ListaMensual\PlanillaListaMensualProceso::class)->guardarOrden((int) $mes, (int) $anio, $listaPlanilla);
    }

    public function obtenerPlanillaXFecha($fecha)
    {
        $carbon = Carbon::parse($fecha);
        return $this->obtenerPlanillaXMesAnio($carbon->month, $carbon->year);
    }

    public function obtenerPlanillaXMesAnio($mes, $anio, $orden = 'orden')
    {
        return PlanMensualDetalle::whereHas('planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)
                ->where('anio', $anio);
        })
            ->with([
                'registrosDiarios',
                'empleado.contratos' => function ($q) use ($mes, $anio) {
                    $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
                    $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
                    $q->where('fecha_inicio', '<=', $finMes)
                        ->where(function ($q2) use ($inicioMes) {
                            $q2->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->limit(1);
                },
            ])
            ->orderBy($orden)
            ->get();
    }

    /** Comisiones y primas de cada AFP vigentes en el mes (una fila por AFP, en orden HABITAT, INTEGRA, PRIMA, PROFUTURO). */
    public static function obtenerDescuentosVigentes($mes, $anio): Collection
    {
        return PlanillaParametroConsulta::afpDelMes((int) $mes, (int) $anio);
    }
}
