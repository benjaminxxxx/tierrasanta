<?php

namespace App\Services\RecursosHumanos\Asistencias\Planilla;

use App\Models\PlanRegistroDiario;
use App\Models\PlanMensualDetalle;
use Carbon\Carbon;

class ResumenAsistenciaPlanillaServicio
{
    public function obtenerResumen($fechaInicio = null, $fechaFin = null, $grupoSeleccionado = null, $filtroNombres = null)
    {
        return PlanRegistroDiario::with(['detalleMensual', 'detalles'])
            ->when($fechaInicio, fn($q) =>
                $q->where('fecha', '>=', $fechaInicio)
            )
            ->when($fechaFin, fn($q) =>
                $q->where('fecha', '<=', $fechaFin)
            )
            ->when($grupoSeleccionado, function ($query) use ($grupoSeleccionado) {
                if ($grupoSeleccionado === 'SG') {
                    $query->whereHas('detalleMensual', fn($q) =>
                        $q->whereNull('grupo')
                    );
                } else {
                    $query->whereHas('detalleMensual', fn($q) =>
                        $q->where('grupo', $grupoSeleccionado)
                    );
                }
            })
            ->when($filtroNombres, function ($query) use ($filtroNombres) {
                $query->whereHas('detalleMensual', function ($q) use ($filtroNombres) {
                    $q->where(function ($sub) use ($filtroNombres) {
                        $sub->where('nombres', 'like', '%' . $filtroNombres . '%')
                            ->orWhere('documento', 'like', '%' . $filtroNombres . '%');
                    });
                });
            })
            ->get()
            ->map(function ($r) {

                $fecha = Carbon::parse($r->fecha);

                $planMensualDetalle = PlanMensualDetalle::with(['planillaMensual'])
                    ->whereHas('planillaMensual', function ($q) use ($fecha) {
                        $q->where('mes', $fecha->month)
                            ->where('anio', $fecha->year);
                    })
                    ->where('plan_empleado_id', $r->detalleMensual->plan_empleado_id ?? 0)
                    ->first();

                $costoXHora = $planMensualDetalle->costo_hora ?? 0;

                $detalle_campos = $r->detalles
                    ->pluck('campo_nombre', 'campo_nombre')
                    ->implode(', ');

                return [
                    'fecha'               => $r->fecha,
                    'codigo_grupo'        => $r->detalleMensual->grupo ?? 'SG',
                    'nombres'             => $r->detalleMensual->nombres ?? '',
                    'costo_x_hora'        => $costoXHora,
                    'asistencia'          => $r->asistencia,
                    'total_horas'         => $r->total_horas,
                    'costo_dia'           => $r->costo_dia,
                    'total_bono'          => $r->total_bono,
                    'esta_pagado'         => $r->esta_pagado,
                    'bono_esta_pagado'    => $r->bono_esta_pagado,
                    'detalle_campos'      => $detalle_campos,
                ];
            })
            ->toArray();
    }
}
