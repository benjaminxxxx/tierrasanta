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
    private function resolverCostoTrabajador($consolidadoOEmpleado, bool $esPlanilla, Carbon $fecha, float $horas): array
    {
        if ($esPlanilla) {
            $costoInfo = $this->obtenerCostoHoraPlanilla($consolidadoOEmpleado, $fecha);
            $costoTotal = $costoInfo['costo_por_hora'] !== null
                ? round($costoInfo['costo_por_hora'] * $horas, 2)
                : 0;

            return ['costo_total' => $costoTotal, 'observacion' => $costoInfo['observacion']];
        }

        // Cuadrilla: lógica de jornal (misma que ya tenías)
        // $consolidadoOEmpleado aquí es el ConsolidadoRiego completo, para leer precio_jornal
        $costoTotal = round(($consolidadoOEmpleado->precio_jornal ?? 0) * ($horas / 8), 2);

        return ['costo_total' => $costoTotal, 'observacion' => null];
    }
    private function combinarObservaciones(?string ...$partes): ?string
    {
        $texto = collect($partes)->filter()->implode(' ');
        return $texto !== '' ? $texto : null;
    }
    private function armarFila(
        string $campania,
        string $fecha,
        string $origenTipo,
        int $origenId,
        string $campo,
        ?string $labor,
        ?string $laborNombre,
        string $trabajador,
        float $horas,
        float $jornales,
        float $costoTotal,
        ?string $observacion
    ): array {
        return [
            'campania' => $campania,
            'fecha' => $fecha,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'campo' => $campo,
            'labor' => $labor,
            'labor_nombre' => $laborNombre,
            'trabajador' => $trabajador,
            'horas' => $horas,
            'cantidad_jornales' => $jornales,
            'costo_total' => $costoTotal,
            'observacion' => $observacion,
        ];
    }
    // --- Fuente 1: planilla directa ---

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
            $horas = CalculoHelper::obtenerDiferenciaHoras($detalleDiario->hora_inicio, $detalleDiario->hora_fin);
            $jornales = CalculoHelper::calcularJornales2($detalleDiario->hora_inicio, $detalleDiario->hora_fin);
            $laborNombre = is_object($detalleDiario->labores) ? ($detalleDiario->labores->nombre_labor ?? null) : null;

            if ($esMarcadorRiego) {
                $tieneReporteRiego = ConsolidadoRiego::where('trabajador_type', PlanEmpleado::class)
                    ->where('trabajador_id', $detalleMensual->plan_empleado_id)
                    ->whereDate('fecha', $registroDiario->fecha)
                    ->exists();

                if ($tieneReporteRiego) {
                    // Ya se procesa en generarFilasRiegoPorCampo() con su detalle real.
                    continue;
                }

                $costo = $this->resolverCostoTrabajador($detalleMensual->plan_empleado_id, true, $fechaCarbon, $horas);

                $filas[] = $this->armarFila(
                    $campania,
                    $registroDiario->fecha,
                    'planilla',
                    $detalleDiario->id,
                    $detalleDiario->campo_nombre,
                    $codigoLabor,
                    $laborNombre,
                    $detalleMensual->nombres ?? '-',
                    $horas,
                    $jornales,
                    $costo['costo_total'],
                    $this->combinarObservaciones('No tiene reporte de riego para este día. Verificar.', $costo['observacion'])
                );
                continue;
            }

            $costo = $this->resolverCostoTrabajador($detalleMensual->plan_empleado_id, true, $fechaCarbon, $horas);

            $filas[] = $this->armarFila(
                $campania,
                $registroDiario->fecha,
                'planilla',
                $detalleDiario->id,
                $detalleDiario->campo_nombre,
                $codigoLabor,
                $laborNombre,
                $detalleMensual->nombres ?? '-',
                $horas,
                $jornales,
                $costo['costo_total'],
                $costo['observacion']
            );
        }

        return $filas;
    }

    // --- Fuente 2: riego real ---

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

            if ($campo === 'FDM' && $registro->por_acumulacion) {
                continue;
            }

            $esPlanilla = $consolidado->trabajador_type === PlanEmpleado::class;
            $origenTipo = $esPlanilla ? 'planilla' : 'cuadrilla';
            $fechaCarbon = Carbon::parse($registro->fecha);

            $horas = $registro->por_acumulacion
                ? round(Carbon::parse($registro->hora_inicio)->diffInMinutes(Carbon::parse($registro->hora_fin)) / 60, 2)
                : (float) ($registro->horas_ponderadas ?? 0);

            // Jornales SIEMPRE derivado de las horas ya resueltas (crudas o ponderadas,
            // según el caso de arriba) — nunca recalculado desde hora_inicio/hora_fin,
            // que representarían el tramo completo sin repartir.
            $jornales = round($horas / 8, 3);

            $costoBase = $esPlanilla ? $consolidado->trabajador_id : $consolidado;
            $costo = $this->resolverCostoTrabajador($costoBase, $esPlanilla, $fechaCarbon, $horas);

            $laborNombre = $registro->por_acumulacion
                ? 'Uso de horas acumuladas (Riego)'
                : ($registro->tipo_labor ?? 'Riego');

            $observacion = $this->combinarObservaciones(
                $consolidado->sincronizado ? null : 'Las horas de riego no coinciden con el registro diario. Verificar.',
                $costo['observacion']
            );

            $filas[] = $this->armarFila(
                $campania,
                $registro->fecha,
                $origenTipo,
                $registro->id,
                $registro->campo,
                null,
                $laborNombre,
                $consolidado->trabajador_nombre,
                $horas,
                $jornales,
                $costo['costo_total'],
                $observacion
            );
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