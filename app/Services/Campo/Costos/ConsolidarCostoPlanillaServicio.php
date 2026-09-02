<?php

namespace App\Services\Campo\Costos;

use App\Models\ConsolidadoRiego;
use App\Models\PlanDetalleHora;
use App\Models\ReporteDiarioRiego;
use App\Support\CalculoHelper;

class ConsolidarCostoPlanillaServicio
{
    public function generarFilas(string $campania, string $campo, string $fechaInicio, ?string $fechaFin = null): array
    {
        $fechaFin = $fechaFin ?? now();

        $filasPlanilla = $this->generarFilasPlanillaDirecta($campania, $campo, $fechaInicio, $fechaFin);
        $filasRiego = $this->generarFilasRiegoPorCampo($campania, $campo, $fechaInicio, $fechaFin);

        return array_merge($filasPlanilla, $filasRiego);
    }

    // Fuente 1: trabajo de campo tal como quedó registrado en planilla,
    // excluyendo el marcador FDM/81 (que nunca representa el campo real trabajado)
    private function generarFilasPlanillaDirecta(string $campania, string $campo, string $fechaInicio, string $fechaFin): array
    {
        $detalleDiarios = PlanDetalleHora::with([
            'registroDiario.detalleMensual.empleado',
            'labores'
        ])
            ->whereHas('registroDiario', function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })
            ->where('campo_nombre', $campo)
            ->get();

        $filas = [];

        foreach ($detalleDiarios as $detalleDiario) {

            $registroDiario = $detalleDiario->registroDiario;
            $detalleMensual = $registroDiario?->detalleMensual;

            if (!$registroDiario || !$detalleMensual) {
                logger()->warning('Registro incompleto en generarFilasPlanillaDirecta', [
                    'plan_detalle_hora_id' => $detalleDiario->id,
                ]);
                continue;
            }

            $codigoLabor = $detalleDiario->labores->codigo ?? null;

            // Caso límite: si la campaña ES el campo "FDM" y esta fila es el marcador 81,
            // ese bloque ya se procesa en generarFilasRiegoPorCampo() — se omite aquí para no duplicar.
            if ($campo === 'FDM' && (string) $codigoLabor === '81') {
                continue;
            }

            $nombreLabor = is_object($detalleDiario->labores)
                ? ($detalleDiario->labores->nombre_labor ?? null)
                : null;

            $filas[] = [
                'campania' => $campania,
                'fecha' => $registroDiario->fecha,
                'origen_tipo' => 'planilla',
                'origen_id' => $detalleDiario->id,
                'campo' => $detalleDiario->campo_nombre,
                'labor' => $codigoLabor,
                'labor_nombre' => $nombreLabor,
                'trabajador' => $detalleMensual->nombres ?? '-',
                'horas' => CalculoHelper::obtenerDiferenciaHoras($detalleDiario->hora_inicio, $detalleDiario->hora_fin),
                'cantidad_jornales' => CalculoHelper::calcularJornales2($detalleDiario->hora_inicio, $detalleDiario->hora_fin),
                'costo_total' => 0,
                'observacion' => null,
            ];
        }

        return $filas;
    }

    // Fuente 2: trabajo de riego real, consultado directo a ReporteDiarioRiego
    // (el ÚNICO lugar donde se guarda el campo verdadero), sin depender de si
    // ese día llegó o no a estar reflejado en registro diario de planilla.
    private function generarFilasRiegoPorCampo(string $campania, string $campo, string $fechaInicio, string $fechaFin): array
    {
        $registros = ReporteDiarioRiego::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('campo', $campo)
            ->whereHas('consolidado', function ($q) {
                $q->where('trabajador_type', \App\Models\PlanEmpleado::class);
            })
            ->with('consolidado')
            ->get();

        $filas = [];

        foreach ($registros as $registro) {
            $consolidado = $registro->consolidado;
            if (!$consolidado) continue;

            $observacion = $consolidado->sincronizado
                ? null
                : 'Las horas de este día no coinciden con el registro diario de planilla. Verificar.';

            $horas = round(
                \Carbon\Carbon::parse($registro->hora_inicio)->diffInMinutes(\Carbon\Carbon::parse($registro->hora_fin)) / 60,
                2
            );
            
            $filas[] = [
                'campania' => $campania,
                'fecha' => $registro->fecha,
                'origen_tipo' => 'riego',
                'origen_id' => $registro->id,
                'campo' => $registro->campo,
                'labor' => null,
                'labor_nombre' => $registro->por_acumulacion ? 'Uso de horas acumuladas' : $registro->tipo_labor,
                'trabajador' => $consolidado->trabajador_nombre,
                'horas' => $horas,
                'cantidad_jornales' => CalculoHelper::calcularJornales2($registro->hora_inicio, $registro->hora_fin),
                'costo_total' => 0,
                'observacion' => $observacion,
            ];
        }

        return $filas;
    }
}