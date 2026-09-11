<?php

namespace App\Services\Campo\Costos;

use App\Models\ConsolidadoRiego;
use App\Models\PlanActividadBono;
use App\Models\PlanDetalleHora;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualPersonal;
use App\Models\ReporteDiarioRiego;
use App\Support\CalculoHelper;
use DB;
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
        /* version antigua que se cambia por lo que se paga realmente sobre las horas, es lo que genera exactitud en planilla 
              if (!$personal || is_null($personal->proyectado_sueldo_por_hora)) {
                  $nombreMes = $this->nombreMes($mes);

                  return $this->cacheCostoPorHora[$clave] = [
                      'costo_por_hora' => null,
                      'observacion' => "Aún no se ha generado la planilla del mes de {$nombreMes} del año {$anio}.",
                  ];
              }*/
        if (!$personal || is_null($personal->pagado_sueldo_por_hora)) {
            $nombreMes = $this->nombreMes($mes);

            return $this->cacheCostoPorHora[$clave] = [
                'costo_por_hora' => null,
                'observacion' => "Aún no se ha generado la planilla del mes de {$nombreMes} del año {$anio}.",
            ];
        }
        //dd($personal->proyectado_sueldo_por_hora);//14.403714353365
        return $this->cacheCostoPorHora[$clave] = [
            'costo_por_hora' => (float) $personal->pagado_sueldo_por_hora,
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
    /*
    Se resolvera por tipos ahora
    public function generarFilas(string $campania, string $campo, string $fechaInicio, ?string $fechaFin = null): array
    {
        $fechaFin = $fechaFin ?? now();

        $filasPlanilla = $this->generarFilasPlanillaDirecta($campania, $campo, $fechaInicio, $fechaFin);
        $filasRiego = $this->generarFilasRiegoPorCampo($campania, $campo, $fechaInicio, $fechaFin);

        return array_merge($filasPlanilla, $filasRiego);
    }
    */
    public function generarFilas(string $campania, string $campo, string $fechaInicio, ?string $fechaFin = null, ?array $tipos = null): array
    {
        $fechaFin = $fechaFin ?? now();
        $todos = $tipos === null;
        $filas = [];

        if ($todos || in_array('planilla', $tipos, true)) {
            $filas = array_merge(
                $filas,
                $this->generarFilasPlanillaDirecta($campania, $campo, $fechaInicio, $fechaFin),
                $this->generarFilasRiegoPorCampo($campania, $campo, $fechaInicio, $fechaFin)
            );
        }

        if ($todos || in_array('bono_productividad', $tipos, true)) {
            $filas = array_merge($filas, $this->generarFilasBonoProductividad($campania, $campo, $fechaInicio, $fechaFin));
        }

        return $filas;
    }
    /**
     * Trae el bono de productividad por actividad (campo+labor), atribuido
     * al campo real donde ocurrió esa actividad — NO al día/trabajador en
     * bloque, para que el prorrateo por campo sea correcto.
     * Solo trabajador, campo y monto — sin horas ni jornales (no aplica).
     */
    private function generarFilasBonoProductividad(string $campania, string $campo, string $fechaInicio, string $fechaFin): array
    {
        $bonos = PlanActividadBono::query()
            ->join('actividades', 'actividades.id', '=', 'plan_actividad_bonos.actividad_id')
            ->join('plan_registros_diarios', 'plan_registros_diarios.id', '=', 'plan_actividad_bonos.registro_diario_id')
            ->join('plan_mensual_detalles', 'plan_mensual_detalles.id', '=', 'plan_registros_diarios.plan_det_men_id')
            ->leftJoin('labores', 'labores.id', '=', 'actividades.labor_id')

            // Hacemos el Join usando codigo_labor comparado con actividades.codigo_labor o labores.codigo
            ->leftJoin('plan_detalles_horas', function ($join) {
                $join->on('plan_detalles_horas.plan_reg_dia_id', '=', 'plan_registros_diarios.id')
                    ->on('plan_detalles_horas.campo_nombre', '=', 'actividades.campo')
                    ->on(function ($query) {
                        // Prioriza actividades.codigo_labor; si es null, recurre a labores.codigo
                        $query->on('plan_detalles_horas.codigo_labor', '=', 'actividades.codigo_labor')
                            ->orOn('plan_detalles_horas.codigo_labor', '=', 'labores.codigo');
                    });
            })

            ->where('actividades.campo', $campo)
            ->whereBetween('plan_registros_diarios.fecha', [$fechaInicio, $fechaFin])
            ->where('plan_actividad_bonos.total_bono', '>', 0)
            ->select(
                'plan_actividad_bonos.id',
                'plan_registros_diarios.fecha',
                'plan_mensual_detalles.nombres',
                'plan_actividad_bonos.total_bono',
                'actividades.labor_id',
                'actividades.codigo_labor as actividad_codigo_labor',
                'labores.codigo as tabla_codigo_labor',
                'actividades.nombre_labor as actividad_nombre_labor',
                'labores.nombre_labor as tabla_nombre_labor',

                DB::raw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, plan_detalles_horas.hora_inicio, plan_detalles_horas.hora_fin)), 0) as total_minutos')
            )
            ->groupBy(
                'plan_actividad_bonos.id',
                'plan_registros_diarios.fecha',
                'plan_mensual_detalles.nombres',
                'plan_actividad_bonos.total_bono',
                'actividades.labor_id',
                'actividades.codigo_labor',
                'labores.codigo',
                'actividades.nombre_labor',
                'labores.nombre_labor'
            )
            ->get();

        $filas = [];

        foreach ($bonos as $bono) {
            $laborNombre = !empty($bono->actividad_nombre_labor)
                ? $bono->actividad_nombre_labor
                : ($bono->tabla_nombre_labor ?? 'Bono de productividad');

            // Determina el código de labor asignado en lugar de usar el ID primario
            $codigoLabor = $bono->actividad_codigo_labor ?? $bono->tabla_codigo_labor ?? (string) $bono->labor_id;

            $minutos = (int) $bono->total_minutos;
            $jornales = $minutos > 0 ? round($minutos / 480, 2) : 0;

            $filas[] = $this->armarFila(
                $campania,
                $bono->fecha,
                'planilla_bono_productividad',
                $bono->id,
                $campo,
                $codigoLabor,
                $laborNombre,
                $bono->nombres ?? '-',
                $minutos,
                $jornales,
                (float) $bono->total_bono,
                null
            );
        }

        return $filas;
    }
    private function resolverCostoTrabajador($consolidadoOEmpleado, bool $esPlanilla, Carbon $fecha, int $minutos): array
    {
        if ($esPlanilla) {
            $costoInfo = $this->obtenerCostoHoraPlanilla($consolidadoOEmpleado, $fecha);
            //dd($costoInfo);
            $costoTotal = $costoInfo['costo_por_hora'] !== null
                ? $costoInfo['costo_por_hora'] * ($minutos / 60)
                : 0;

            return ['costo_total' => $costoTotal, 'observacion' => $costoInfo['observacion']];
        }

        // Cuadrilla: lógica de jornal (misma que ya tenías, ahora desde minutos)
        $costoTotal = ($consolidadoOEmpleado->precio_jornal ?? 0) * ($minutos / 480); // 480 min = 1 jornal de 8h

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
        int $minutos,
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
            'minutos' => $minutos,
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
            $minutos = CalculoHelper::obtenerDiferenciaMinutos($detalleDiario->hora_inicio, $detalleDiario->hora_fin);
            $jornales = round($minutos / 480, 3);
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

                $costo = $this->resolverCostoTrabajador($detalleMensual->plan_empleado_id, true, $fechaCarbon, $minutos);

                $filas[] = $this->armarFila(
                    $campania,
                    $registroDiario->fecha,
                    'planilla',
                    $detalleDiario->id,
                    $detalleDiario->campo_nombre,
                    $codigoLabor,
                    $laborNombre,
                    $detalleMensual->nombres ?? '-',
                    $minutos,
                    $jornales,
                    $costo['costo_total'],
                    $this->combinarObservaciones('No tiene reporte de riego para este día. Verificar.', $costo['observacion'])
                );
                continue;
            }

            $costo = $this->resolverCostoTrabajador($detalleMensual->plan_empleado_id, true, $fechaCarbon, $minutos);

            $filas[] = $this->armarFila(
                $campania,
                $registroDiario->fecha,
                'planilla',
                $detalleDiario->id,
                $detalleDiario->campo_nombre,
                $codigoLabor,
                $laborNombre,
                $detalleMensual->nombres ?? '-',
                $minutos,
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

            $minutos = $registro->por_acumulacion
                ? Carbon::parse($registro->hora_inicio)->diffInMinutes(Carbon::parse($registro->hora_fin)) // exacto
                : (int) round(($registro->horas_ponderadas ?? 0) * 60); // única conversión, desde el valor ya ponderado


            // Jornales SIEMPRE derivado de las horas ya resueltas (crudas o ponderadas,
            // según el caso de arriba) — nunca recalculado desde hora_inicio/hora_fin,
            // que representarían el tramo completo sin repartir.
            $jornales = round($minutos / 480, 3);
            $trabajadorId = $esPlanilla ? $consolidado->trabajador_id : $consolidado;

            $costo = $this->resolverCostoTrabajador($trabajadorId, $esPlanilla, $fechaCarbon, $minutos);

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
                $minutos,
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