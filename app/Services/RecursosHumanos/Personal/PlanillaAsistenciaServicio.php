<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\PlanillaAsistencia;
use App\Models\PlanillaAsistenciaDetalle;
use App\Models\ReporteDiario;
use App\Support\DateHelper;
use DB;
use Illuminate\Support\Carbon;

class PlanillaAsistenciaServicio
{
    /*
    public function generarResumenAsistencia(int $mes, int $anio): void
    {
        DB::transaction(function () use ($mes, $anio) {
            $reportesDiarios = ReporteDiario::whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->orderBy('orden')
                ->get();

            $reportesAgrupados = $reportesDiarios->groupBy('documento');
            $detalles = [];

            $documentos = $reportesAgrupados->keys();
            $planillaIds = PlanillaAsistencia::whereIn('documento', $documentos)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->pluck('id');

            PlanillaAsistenciaDetalle::whereIn('planilla_asistencia_id', $planillaIds)->delete();

            foreach ($reportesAgrupados as $documento => $reportes) {
                $primerReporte = $reportes->first();

                $planillaAsistencia = PlanillaAsistencia::updateOrCreate(
                    [
                        'documento' => $documento,
                        'mes' => $mes,
                        'anio' => $anio,
                    ],
                    [
                        'grupo' => $primerReporte->tipo_trabajador,
                        'nombres' => $primerReporte->empleado_nombre,
                        'orden' => $primerReporte->orden,
                        'total_horas' => 0,
                    ]
                );

                $totalHorasDecimal = 0;
                $diasEnMes = Carbon::create($anio, $mes)->daysInMonth;

                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    $fechaDia = Carbon::create($anio, $mes, $dia);
                    $detalle = $reportes->firstWhere('fecha', $fechaDia->toDateString());

                    if ($detalle) {
                        $horasDecimal = DateHelper::convertirHorasADecimal($detalle->total_horas);
                        $totalHorasDecimal += $horasDecimal;

                        $detalles[] = [
                            'planilla_asistencia_id' => $planillaAsistencia->id,
                            'fecha' => $detalle->fecha,
                            'tipo_asistencia' => $detalle->asistencia,
                            'horas_jornal' => $horasDecimal,
                        ];
                    }
                }

                $planillaAsistencia->total_horas = $totalHorasDecimal;
                $planillaAsistencia->save();
            }

            if (!empty($detalles)) {
                PlanillaAsistenciaDetalle::insert($detalles);
            }
        });
    }*/
}