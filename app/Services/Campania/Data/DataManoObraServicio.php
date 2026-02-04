<?php

namespace App\Services\Campania\Data;

use App\Models\CampoCampania;
use App\Models\CuadDetalleHora;
use App\Models\PlanDetalleHora;
use App\Support\CalculoHelper;
use App\Support\DateHelper;
use Exception;

class DataManoObraServicio
{
    public function generarPlanillerosPor($campo, $fechaInicio, $fechaFin = null)
    {
        $fechaFin = $fechaFin ?? now();
        $detalleDiarios = PlanDetalleHora::with(['registroDiario.detalleMensual.empleado', 'labores'])->whereHas('registroDiario', function ($registroDiario) use ($fechaInicio, $fechaFin) {
            return $registroDiario->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        })
            ->where('campo_nombre', $campo)
            ->get()
            ->toArray();
        $data = [];
        foreach ($detalleDiarios as $i => $detalleDiario) {

            $genero = '-';

            if (isset($detalleDiario['registro_diario']['detalle_mensual']['empleado'])) {
                $genero = $detalleDiario['registro_diario']['detalle_mensual']['empleado']['genero'];
            }
            $fecha = $detalleDiario['registro_diario']['fecha'];
            $trabajador = $detalleDiario['registro_diario']['detalle_mensual']['nombres'];
            
            $manoObra = $detalleDiario['labores']['nombre_labor'];
            $jornalDiario = (float) $detalleDiario['registro_diario']['detalle_mensual']['jornal_diario'];

            $cantidadJornales = CalculoHelper::calcularJornales2($detalleDiario['hora_inicio'], $detalleDiario['hora_fin']);
            $totalHoras = CalculoHelper::obtenerDiferenciaHoras($detalleDiario['hora_inicio'], $detalleDiario['hora_fin']);
            $data[] = [
                'fecha' => $fecha,
                'horas' => $totalHoras,
                'planilla_nombre' => $trabajador,
                'sexo' => $genero,
                'mano_obra' => $manoObra,
                'cantidad_jornales' => $cantidadJornales,
                'costo' => ($jornalDiario * $cantidadJornales),
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
