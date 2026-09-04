<?php

namespace App\Services\Campania\Data;

use App\Models\CampoCampania;
use App\Models\CuadDetalleHora;
use App\Models\PlanDetalleHora;
use App\Models\ResumenCostoDiario;
use App\Support\CalculoHelper;
use App\Support\DateHelper;
use Exception;

class DataManoObraServicio
{
    /**
     * Obtiene los registros consolidados de planilla para el reporte BDD Mensual.
     *
     * @param string $campania
     * @param string $campo
     * @return array
     */
    public function generarPlanillerosPor(string $campania, string $campo): array
    {
        $registros = ResumenCostoDiario::where('campania', $campania)
            ->where('campo', $campo)
            ->where('origen_tipo', 'planilla')
            ->orderBy('fecha', 'asc')
            ->get();

        $data = [];

        foreach ($registros as $row) {
            $data[] = [
                'fecha' => $row->fecha ? $row->fecha->format('Y-m-d') : null,
                'horas' => (float) $row->horas,
                'planilla_nombre' => $row->trabajador ?? '-',
                'sexo' => '-', // Mantener estructura por compatibilidad
                'mano_obra' => $row->labor_nombre ?? '-',
                'cantidad_jornales' => (float) $row->cantidad_jornales,
                'costo' => (float) $row->costo_total,
            ];
        }

        return $data;
    }

    public function generarCuaderillerosPor($campo, $fechaInicio, $fechaFin = null)
    {
        $fechaFin = $fechaFin ?? now();
        $detalleDiarios = CuadDetalleHora::with(['registroDiario.cuadrillero', 'labores'])
            ->whereHas('registroDiario', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })
            ->where('campo_nombre', $campo)
            ->get();

        $agrupados = [];

        foreach ($detalleDiarios as $detalle) {
            $registro = $detalle->registroDiario;

            $fecha = $registro->fecha;
            $manoObra = $detalle->labores->nombre_labor ?? 'N/A';

            // Calculamos el costo unitario de este trabajador para agruparlo
            $costoDia = CalculoHelper::valorNumerico($registro->costo_dia ?? 0);
            $totalHorasDia = CalculoHelper::valorNumerico($registro->total_horas ?? 8);
            $costoPorHora = ($totalHorasDia > 0) ? ($costoDia / $totalHorasDia) : 0;

            // Creamos una llave única: Fecha + Labor + CostoHora
            // Si dos trabajadores tienen estos 3 datos iguales, caerán en la misma bolsa
            $key = "{$fecha}_{$manoObra}_" . round($costoPorHora, 2);

            $horasItem = CalculoHelper::obtenerDiferenciaHoras($detalle->hora_inicio, $detalle->hora_fin);
            $jornalesItem = CalculoHelper::calcularJornales2($detalle->hora_inicio, $detalle->hora_fin);
            $costoItem = ($costoPorHora * $horasItem);

            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'fecha' => $fecha->format('Y-m-d'),
                    'mano_obra' => $manoObra,
                    'cuadrilla_cantidad' => 0,
                    'cuadrilla_costo' => round($costoPorHora, 2),
                    'horas' => 0,
                    'cantidad_jornales' => 0,
                    'costo' => 0,
                ];
            }

            // Acumulamos los valores
            $agrupados[$key]['cuadrilla_cantidad'] += 1;
            $agrupados[$key]['horas'] += $horasItem;
            $agrupados[$key]['cantidad_jornales'] += $jornalesItem;
            $agrupados[$key]['costo'] += $costoItem;
        }

        // Reindexamos el array para que sea una lista simple
        return array_values($agrupados);
    }
}
