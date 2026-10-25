<?php

namespace App\Services\Planilla\Empleado;

use App\Exceptions\ErrorControlado;
use App\Models\PlanContrato;
use App\Services\Planilla\Laboral\PlanillaLaboralConsulta;
use App\Services\Planilla\Laboral\PlanillaLaboralProceso;
use Carbon\Carbon;

/**
 * Panel general de contratos: listado con filtros, cierre y eliminación. Toda regla y escritura va a la capa laboral
 * (PlanillaLaboralConsulta / PlanillaLaboralProceso); aquí solo se adapta para las pantallas.
 */
class ContratoServicio
{
    /** Cierre del contrato (fecha de fin y motivo SUNAT). */
    public function finalizarContrato(int $contratoId, array $datosCierre): PlanContrato
    {
        if (blank($datosCierre['fecha_fin'] ?? null) || blank($datosCierre['motivo_cese_sunat'] ?? null)) {
            throw new ErrorControlado('La fecha de fin y el motivo de cese son obligatorios.');
        }
        return app(PlanillaLaboralProceso::class)->finalizarContrato(PlanContrato::findOrFail($contratoId),
            Carbon::parse($datosCierre['fecha_fin'])->toDateString(), $datosCierre['motivo_cese_sunat'], $datosCierre['comentario_cese'] ?? null);
    }

    public function eliminarContrato($contratoId)
    {
        app(PlanillaLaboralProceso::class)->eliminarContrato(PlanContrato::findOrFail($contratoId));
    }

    /** Contratos que cruzan el mes (por empleado). */
    public static function obtenerContratosVigentes(int $mes, int $anio)
    {
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        return PlanContrato::enRango($inicioMes, $inicioMes->copy()->endOfMonth())->get()->keyBy('plan_empleado_id');
    }

    /**
     * Lista contratos con filtros. Estado: activo (vigente hoy), finalizado (ya terminó). Grupo y cargo: los vigentes hoy.
     */
    public function listarContratos(array $filtros = [], $perPage = null)
    {
        $query = PlanContrato::query()->with(['empleado', 'asignaciones.grupo', 'asignaciones.cargo', 'remuneraciones']);

        if (!empty($filtros['buscar'])) {
            $query->whereHas('empleado', fn($q) => $q->buscar($filtros['buscar']));
        }
        match ($filtros['estado'] ?? '') {
            'activo', 'en_prueba' => $query->vigentes(),
            'finalizado' => $query->finalizados(),
            default => null,
        };
        $query->when(!empty($filtros['tipo_planilla']), fn($q) => $q->where('tipo_planilla', $filtros['tipo_planilla']));
        if (!empty($filtros['grupo_codigo'])) {
            $query->whereIn('plan_empleado_id', PlanillaLaboralConsulta::filtrarEmpleadosPorGrupo(\App\Models\PlanEmpleado::query(), $filtros['grupo_codigo'], now())->select('plan_empleados.id'));
        }
        if (!empty($filtros['cargo_id'])) {
            $query->whereIn('plan_empleado_id', PlanillaLaboralConsulta::ocupantesPorCargo()[(int) $filtros['cargo_id']] ?? [0]);
        }
        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_inicio', '<=', $filtros['fecha_hasta']);
        }
        $query->orderBy('fecha_inicio', 'desc');

        return $perPage ? $query->paginate($perPage) : $query->get();
    }
}
