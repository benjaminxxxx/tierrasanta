<?php

namespace App\Services\Handsontable;

use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Models\PlanResumenDiarioTipoAsistencia;
use App\Services\PlanillaMensualServicio;
use Illuminate\Support\Carbon;
class HSTPlanillaAsistencia
{
  public function obtenerAsistenciaMensualAgraria($mes, $anio)
  {
    // Obtener planillas con sus registros diarios
    $planillaDetalles = app(PlanillaMensualServicio::class)->obtenerPlanillaXMesAnio($mes, $anio);
    $ultimoDiaMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->day;

    $empleados = $planillaDetalles->map(function ($detalle) use ($mes, $anio, $ultimoDiaMes) {

      $empleadoData = [
        'plan_men_detalle_id' => $detalle->id,
        'grupo' => $detalle->grupo,
        'documento' => $detalle->documento,
        'nombres' => mb_strtoupper($detalle->nombres),
      ];

      // Agrupamos los registros diarios por fecha => total_horas
      $totalesPorDia = $detalle->registrosDiarios
        ->groupBy(fn($r) => Carbon::parse($r->fecha)->day)
        ->map(fn($regs) => $regs->sum('total_horas'));
      // Asignar día por día (si no hay registro, dejar en 0)
      $total = 0;
      for ($i = 1; $i <= $ultimoDiaMes; $i++) {
        $totalPorDia = $totalesPorDia->get($i, 0);
        $total+=$totalPorDia;
        $empleadoData['dia_' . $i] = $totalPorDia;
      }
      $empleadoData['total_horas'] = $total; 

      return $empleadoData;
    })->toArray();

    return $empleados;
  }
  public function obtenerInformacionAsistenciaAdicional($mes, $anio)
  {
    $resumenes = PlanResumenDiario::with(['totales'])
      ->whereMonth('fecha', $mes)
      ->whereYear('fecha', $anio)
      ->get();

    $informacionAsistenciaAdicional = [];

    foreach ($resumenes as $resumen) {
      $dia = Carbon::parse($resumen->fecha)->day;
      $diaKey = "dia_{$dia}";

      foreach ($resumen->totales as $tipoAsistencia) {

        $empleados = PlanRegistroDiario::with('detalleMensual')
          ->whereDate('fecha', $resumen->fecha)
          ->where('asistencia', $tipoAsistencia->codigo)
          ->get();

        foreach ($empleados as $empleado) {
          $documento = optional($empleado->detalleMensual)->documento;

          if (!$documento) {
            continue; // salta si no tiene documento
          }

          $informacionAsistenciaAdicional[$diaKey][$documento] = [
            'tipo_asistencia' => $tipoAsistencia->codigo,
            'color' => $tipoAsistencia->color ?? '#ffffff',
            'descripcion' => $tipoAsistencia->descripcion ?? '',
          ];
        }
      }
    }
    return $informacionAsistenciaAdicional;
  }
}