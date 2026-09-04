<?php

namespace App\Services\Campo\Costos;

use App\Models\ConsolidadoRiego;
use App\Models\PlanDetalleHora;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualPersonal;
use App\Models\ReporteDiarioRiego;
use App\Support\CalculoHelper;
use Illuminate\Support\Carbon;

class ConsolidarCostoPlanillaServicio
{
    private array $cacheCostoPorHora = [];
    private function obtenerCostoHoraPlanilla(int $planEmpleadoId, Carbon $fecha): array
    {
        $mes = $fecha->month;
        $anio = $fecha->year;
        $clave = "{$planEmpleadoId}-{$mes}-{$anio}";

        if (array_key_exists($clave, $this->cacheCostoPorHora)) {
            return $this->cacheCostoPorHora[$clave];
        }

        $personal = PlanMensualPersonal::where('plan_empleado_id', $planEmpleadoId)
            ->whereHas('planMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
            ->first();

        if (!$personal || is_null($personal->proyectado_sueldo_por_hora)) {
            $nombreMes = $this->nombreMes($mes);

            return $this->cacheCostoPorHora[$clave] = [
                'costo_por_hora' => null,
                'observacion' => "Aún no se ha generado la planilla del mes de {$nombreMes} del año {$anio}.",
            ];
        }

        return $this->cacheCostoPorHora[$clave] = [
            'costo_por_hora' => (float) $personal->proyectado_sueldo_por_hora,
            'observacion' => null,
        ];
    }
    private function nombreMes(int $mes): string
    {
        $nombres = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $nombres[$mes] ?? (string) $mes;
    }
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
            $esMarcadorRiego = $campo === 'FDM' && (string) $codigoLabor === '81';
            $fechaCarbon = Carbon::parse($registroDiario->fecha);

            if ($esMarcadorRiego) {
                $tieneReporteRiego = ConsolidadoRiego::where('trabajador_type', PlanEmpleado::class)
                    ->where('trabajador_id', $detalleMensual->plan_empleado_id)
                    ->whereDate('fecha', $registroDiario->fecha)
                    ->exists();

                if ($tieneReporteRiego) {
                    // Este bloque ya se procesa en generarFilasRiegoPorCampo() con su detalle real.
                    continue;
                }

                // Se pagó como marcador FDM/81 pero no existe respaldo de riego ese día.
                $horas = CalculoHelper::obtenerDiferenciaHoras($detalleDiario->hora_inicio, $detalleDiario->hora_fin);
                $costoInfo = $this->obtenerCostoHoraPlanilla($detalleMensual->plan_empleado_id, $fechaCarbon);
                $costoTotal = $costoInfo['costo_por_hora'] !== null ? round($costoInfo['costo_por_hora'] * $horas, 2) : 0;

                $observacion = collect([
                    'No tiene reporte de riego para este día. Verificar.',
                    $costoInfo['observacion'],
                ])->filter()->implode(' ');

                $filas[] = [
                    'campania' => $campania,
                    'fecha' => $registroDiario->fecha,
                    'origen_tipo' => 'planilla',
                    'origen_id' => $detalleDiario->id,
                    'campo' => $detalleDiario->campo_nombre,
                    'labor' => $codigoLabor,
                    'labor_nombre' => is_object($detalleDiario->labores) ? ($detalleDiario->labores->nombre_labor ?? null) : null,
                    'trabajador' => $detalleMensual->nombres ?? '-',
                    'horas' => $horas,
                    'cantidad_jornales' => CalculoHelper::calcularJornales2($detalleDiario->hora_inicio, $detalleDiario->hora_fin),
                    'costo_total' => $costoTotal,
                    'observacion' => $observacion,
                ];
                continue;
            }

            $nombreLabor = is_object($detalleDiario->labores)
                ? ($detalleDiario->labores->nombre_labor ?? null)
                : null;

            $horas = CalculoHelper::obtenerDiferenciaHoras($detalleDiario->hora_inicio, $detalleDiario->hora_fin);
            $costoInfo = $this->obtenerCostoHoraPlanilla($detalleMensual->plan_empleado_id, $fechaCarbon);
            $costoTotal = $costoInfo['costo_por_hora'] !== null ? round($costoInfo['costo_por_hora'] * $horas, 2) : 0;

            $filas[] = [
                'campania' => $campania,
                'fecha' => $registroDiario->fecha,
                'origen_tipo' => 'planilla',
                'origen_id' => $detalleDiario->id,
                'campo' => $detalleDiario->campo_nombre,
                'labor' => $codigoLabor,
                'labor_nombre' => $nombreLabor,
                'trabajador' => $detalleMensual->nombres ?? '-',
                'horas' => $horas,
                'cantidad_jornales' => CalculoHelper::calcularJornales2($detalleDiario->hora_inicio, $detalleDiario->hora_fin),
                'costo_total' => $costoTotal,
                'observacion' => $costoInfo['observacion'],
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
            ->with('consolidado')
            ->get();

        $filas = [];

        foreach ($registros as $registro) {
            $consolidado = $registro->consolidado;
            if (!$consolidado) {
                continue;
            }

            // Determinar dinámicamente si el registro de riego pertenece a Planilla o a Cuadrilla
            $esPlanilla = $consolidado->trabajador_type === PlanEmpleado::class;
            $origenTipo = $esPlanilla ? 'planilla' : 'cuadrilla';

            $fechaCarbon = Carbon::parse($registro->fecha);

            $horas = round(
                Carbon::parse($registro->hora_inicio)->diffInMinutes(Carbon::parse($registro->hora_fin)) / 60,
                2
            );

            // Obtener costo según el origen del trabajador
            if ($esPlanilla) {
                $costoInfo = $this->obtenerCostoHoraPlanilla($consolidado->trabajador_id, $fechaCarbon);
                $costoTotal = $costoInfo['costo_por_hora'] !== null
                    ? round($costoInfo['costo_por_hora'] * $horas, 2)
                    : 0;
                $obsCosto = $costoInfo['observacion'];
            } else {
                // Lógica para calcular costo por hora/jornal de cuadrilla
                $costoTotal = round(($consolidado->precio_jornal ?? 0) * ($horas / 8), 2);
                $obsCosto = null;
            }

            $observacion = collect([
                $consolidado->sincronizado ? null : 'Las horas de riego no coinciden con el registro diario. Verificar.',
                $obsCosto,
            ])->filter()->implode(' ');

            $filas[] = [
                'campania' => $campania,
                'fecha' => $registro->fecha,
                'origen_tipo' => $origenTipo, // Ahora asigna 'planilla' o 'cuadrilla'
                'origen_id' => $registro->id,
                'campo' => $registro->campo,
                'labor' => null, // O el código configurado para la labor de riego
                'labor_nombre' => $registro->por_acumulacion ? 'Uso de horas acumuladas (Riego)' : ($registro->tipo_labor ?? 'Riego'),
                'trabajador' => $consolidado->trabajador_nombre,
                'horas' => $horas,
                'cantidad_jornales' => CalculoHelper::calcularJornales2($registro->hora_inicio, $registro->hora_fin),
                'costo_total' => $costoTotal,
                'observacion' => $observacion !== '' ? $observacion : null,
            ];
        }

        return $filas;
    }
    /*
    private function generarFilasRiegoPorCampo(string $campania, string $campo, string $fechaInicio, string $fechaFin): array
    {
        $registros = ReporteDiarioRiego::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('campo', $campo)
            ->whereHas('consolidado', function ($q) {
                $q->where('trabajador_type', PlanEmpleado::class);
            })
            ->with('consolidado')
            ->get();

        $filas = [];

        foreach ($registros as $registro) {
            $consolidado = $registro->consolidado;
            if (!$consolidado)
                continue;

            $fechaCarbon = Carbon::parse($registro->fecha);

            $horas = round(
                Carbon::parse($registro->hora_inicio)->diffInMinutes(Carbon::parse($registro->hora_fin)) / 60,
                2
            );

            $costoInfo = $this->obtenerCostoHoraPlanilla($consolidado->trabajador_id, $fechaCarbon);

            $costoTotal = $costoInfo['costo_por_hora'] !== null
                ? round($costoInfo['costo_por_hora'] * $horas, 2)
                : 0;

            // Combinar ambas posibles observaciones (desincronización + planilla no generada)
            // sin perder ninguna si ambas aplican al mismo registro.
            $observacion = collect([
                $consolidado->sincronizado ? null : 'Las horas de este día no coinciden con el registro diario de planilla. Verificar.',
                $costoInfo['observacion'],
            ])->filter()->implode(' ');

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
                'costo_total' => $costoTotal,
                'observacion' => $observacion !== '' ? $observacion : null,
            ];
        }

        return $filas;
    }*/
}