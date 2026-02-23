<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\Labores;
use App\Models\PlanDetalleHora;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\PlanResumenDiario;
use App\Models\PlanResumenDiarioTipoAsistencia;
use App\Models\PlanSuspension;
use App\Models\PlanTipoAsistencia;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\PlanTipoAsistenciaServicio;
use App\Support\CalculoHelper;
use App\Support\FormatoHelper;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanillaRegistroDiarioProcesoSuspensionesPendientes
{
    /**
     * Obtiene el detalle de suspensiones pendientes por registrar.
     * 
     * Agrupa días consecutivos de inasistencia (diferentes de 'A') en un solo registro,
     * y excluye aquellos que ya tienen una suspensión formal registrada.
     *
     * @param int $mes
     * @param int $anio
     * @return array
     */
    public static function obtenerDetalleSuspension(int $mes, int $anio): array
    {
        $anio = 2026;
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();

        // 1. Obtener todos los registros diarios del mes con asistencia diferente de 'A'
        $registrosDiarios = PlanRegistroDiario::query()
            ->with(['detalleMensual.empleado'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('asistencia', '!=', 'A')
            ->orderBy('plan_det_men_id')
            ->orderBy('fecha')
            ->get();

        if ($registrosDiarios->isEmpty()) {
            return [];
        }

        // 2. Obtener IDs de empleados únicos
        $empleadoIds = $registrosDiarios
            ->pluck('detalleMensual.plan_empleado_id')
            ->unique()
            ->filter()
            ->values();

        // 3. Obtener todas las suspensiones registradas
        $suspensionesRegistradas = PlanSuspension::query()
            ->whereIn('plan_empleado_id', $empleadoIds)
            ->where(function($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_inicio', [$fechaInicio->copy()->subMonths(2), $fechaFin->copy()->addMonths(2)])
                    ->orWhereBetween('fecha_fin', [$fechaInicio->copy()->subMonths(2), $fechaFin->copy()->addMonths(2)])
                    ->orWhere(function($q) use ($fechaInicio, $fechaFin) {
                        $q->where('fecha_inicio', '<=', $fechaInicio)
                          ->where(function($q2) use ($fechaFin) {
                              $q2->whereNull('fecha_fin')
                                 ->orWhere('fecha_fin', '>=', $fechaFin);
                          });
                    });
            })
            ->get();

        // 4. Agrupar registros por empleado
        $registrosPorEmpleado = $registrosDiarios->groupBy('plan_det_men_id');

        $resultado = [];

        // 5. Procesar cada empleado
        foreach ($registrosPorEmpleado as $detalleMensualId => $registros) {
            $empleado = $registros->first()->detalleMensual;
            
            if (!$empleado || !$empleado->empleado) {
                continue;
            }

            $empleadoId = $empleado->plan_empleado_id;
            $empleadoNombre = $empleado->nombres;

            // 6. Agrupar días consecutivos de inasistencia
            $gruposConsecutivos = self::agruparDiasConsecutivos($registros);

            // 7. Filtrar grupos que NO están dentro de suspensiones registradas
            $gruposPendientes = self::filtrarGruposPendientes(
                $gruposConsecutivos,
                $suspensionesRegistradas,
                $empleadoId
            );

            // 8. Formatear resultado
            foreach ($gruposPendientes as $grupo) {
                $detalle = self::generarDetalle(
                    $empleadoNombre,
                    $grupo['tipo_asistencia'],
                    $grupo['dias_consecutivos'],
                    $grupo['fecha_inicio'],
                    $grupo['fecha_fin']
                );

                $resultado[] = [
                    'plan_empleado_id' => $empleadoId,
                    'tipo_asistencia' => $grupo['tipo_asistencia'],
                    'fecha_inicio' => $grupo['fecha_inicio'],
                    'fecha_fin' => $grupo['fecha_fin'],
                    'detalle' => $detalle,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Agrupa días consecutivos de inasistencia.
     * ✅ CORREGIDO: Ahora agrupa correctamente días consecutivos
     *
     * @param Collection $registros
     * @return array
     */
    private static function agruparDiasConsecutivos(Collection $registros): array
    {
        if ($registros->isEmpty()) {
            return [];
        }

        $grupos = [];
        $grupoActual = null;

        foreach ($registros as $registro) {
            $fechaActual = Carbon::parse($registro->fecha);
            $tipoAsistencia = $registro->asistencia;

            // ✅ Primer registro: iniciar grupo
            if ($grupoActual === null) {
                $grupoActual = [
                    'fecha_inicio' => $fechaActual->copy(),
                    'fecha_fin' => $fechaActual->copy(),
                    'dias_consecutivos' => 1,
                    'tipo_asistencia' => $tipoAsistencia,
                ];
                continue;
            }

            // ✅ Verificar si es día consecutivo
            $fechaFinGrupo = $grupoActual['fecha_fin'];
            $diaSiguiente = $fechaFinGrupo->copy()->addDay();
            $esConsecutivo = $fechaActual->isSameDay($diaSiguiente);
            $mismoTipo = $tipoAsistencia === $grupoActual['tipo_asistencia'];

            if ($esConsecutivo && $mismoTipo) {
                // ✅ Extender el grupo actual
                $grupoActual['fecha_fin'] = $fechaActual->copy();
                $grupoActual['dias_consecutivos']++;
            } else {
                // ✅ Guardar grupo actual y empezar uno nuevo
                $grupos[] = [
                    'fecha_inicio' => $grupoActual['fecha_inicio']->format('Y-m-d'),
                    'fecha_fin' => $grupoActual['fecha_fin']->format('Y-m-d'),
                    'dias_consecutivos' => $grupoActual['dias_consecutivos'],
                    'tipo_asistencia' => $grupoActual['tipo_asistencia'],
                ];
                
                $grupoActual = [
                    'fecha_inicio' => $fechaActual->copy(),
                    'fecha_fin' => $fechaActual->copy(),
                    'dias_consecutivos' => 1,
                    'tipo_asistencia' => $tipoAsistencia,
                ];
            }
        }

        // ✅ Agregar el último grupo
        if ($grupoActual !== null) {
            $grupos[] = [
                'fecha_inicio' => $grupoActual['fecha_inicio']->format('Y-m-d'),
                'fecha_fin' => $grupoActual['fecha_fin']->format('Y-m-d'),
                'dias_consecutivos' => $grupoActual['dias_consecutivos'],
                'tipo_asistencia' => $grupoActual['tipo_asistencia'],
            ];
        }

        return $grupos;
    }

    /**
     * Genera el texto descriptivo del detalle.
     *
     * @param string $nombreEmpleado
     * @param string $tipoAsistencia
     * @param int $diasConsecutivos
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return string
     */
    private static function generarDetalle(
        string $nombreEmpleado,
        string $tipoAsistencia,
        int $diasConsecutivos,
        string $fechaInicio,
        string $fechaFin
    ): string {
        $inicio = Carbon::parse($fechaInicio)->format('d/m/Y');
        $fin = Carbon::parse($fechaFin)->format('d/m/Y');

        if ($fechaInicio === $fechaFin) {
            $rangoFecha = "en la fecha {$inicio}";
        } else {
            $rangoFecha = "desde el {$inicio} hasta el {$fin}";
        }

        $tipoTexto = self::obtenerTextoTipoAsistencia($tipoAsistencia, $diasConsecutivos);

        return "{$nombreEmpleado} tiene {$diasConsecutivos} {$tipoTexto} {$rangoFecha}";
    }

    /**
     * Obtiene el texto descriptivo del tipo de asistencia.
     *
     * @param string $tipoAsistencia
     * @param int $cantidad
     * @return string
     */
    private static function obtenerTextoTipoAsistencia(string $tipoAsistencia, int $cantidad): string
    {
        $singular = $cantidad === 1;

        $textos = [
            'F' => $singular ? 'falta' : 'faltas',
            'LCG' => $singular ? 'licencia con goce' : 'licencias con goce',
            'LSG' => $singular ? 'licencia sin goce' : 'licencias sin goce',
            'AM' => $singular ? 'falta por enfermedad' : 'faltas por enfermedad',
            'V' => $singular ? 'día de vacaciones' : 'días de vacaciones',
            'D' => $singular ? 'descanso médico' : 'descansos médicos',
            'P' => $singular ? 'permiso' : 'permisos',
        ];

        return $textos[$tipoAsistencia] ?? ($singular ? $tipoAsistencia : "{$tipoAsistencia}s");
    }

    /**
     * Filtra grupos que NO están dentro de suspensiones ya registradas.
     *
     * @param array $grupos
     * @param Collection $suspensionesRegistradas
     * @param int $empleadoId
     * @return array
     */
    private static function filtrarGruposPendientes(
        array $grupos,
        Collection $suspensionesRegistradas,
        int $empleadoId
    ): array {
        return array_filter($grupos, function ($grupo) use ($suspensionesRegistradas, $empleadoId) {
            $fechaInicio = Carbon::parse($grupo['fecha_inicio']);
            $fechaFin = Carbon::parse($grupo['fecha_fin']);

            $estaCubierto = $suspensionesRegistradas
                ->where('plan_empleado_id', $empleadoId)
                ->contains(function ($suspension) use ($fechaInicio, $fechaFin) {
                    return self::rangoEstaDentroSuspension(
                        $fechaInicio,
                        $fechaFin,
                        $suspension->fecha_inicio,
                        $suspension->fecha_fin
                    );
                });

            return !$estaCubierto;
        });
    }

    /**
     * Verifica si un rango de fechas está completamente dentro de una suspensión.
     *
     * @param Carbon $rangoInicio
     * @param Carbon $rangoFin
     * @param Carbon $suspensionInicio
     * @param Carbon|null $suspensionFin
     * @return bool
     */
    private static function rangoEstaDentroSuspension(
        Carbon $rangoInicio,
        Carbon $rangoFin,
        Carbon $suspensionInicio,
        ?Carbon $suspensionFin
    ): bool {
        if ($suspensionFin === null) {
            return $rangoInicio->greaterThanOrEqualTo($suspensionInicio);
        }

        return $rangoInicio->greaterThanOrEqualTo($suspensionInicio)
            && $rangoInicio->lessThanOrEqualTo($suspensionFin)
            && $rangoFin->greaterThanOrEqualTo($suspensionInicio)
            && $rangoFin->lessThanOrEqualTo($suspensionFin);
    }
}