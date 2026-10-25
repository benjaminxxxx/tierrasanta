<?php

namespace App\Services\Planilla;

use App\Models\PlanEmpleado;
use App\Services\Planilla\Laboral\PlanillaLaboralConsulta;
use App\Services\Planilla\Laboral\PlanillaLaboralProceso;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Listados de empleados de planilla (por mes y tipo de planilla, búsqueda con filtros, orden). Los datos personales
 * se buscan en la persona (scopes de PlanEmpleado) y el historial laboral con PlanillaLaboralConsulta.
 * Guardar un empleado: PlanillaLaboralProceso::guardarEmpleado (aquí solo se delega).
 */
class PlanillaEmpleadoServicio
{
    public function guardar(array $datos, ?int $empleadoId = null): PlanEmpleado
    {
        return app(PlanillaLaboralProceso::class)->guardarEmpleado($datos, $empleadoId);
    }

    // ─── BASE: query reutilizable por tipo de planilla ────────────────────────
    private static function queryPlanilla(int $mes, int $anio, string $tipoPlanilla): Builder
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        return PlanEmpleado::query()
            ->whereNull('deleted_at')
            ->whereHas('contratos', fn($q) => $q->where('tipo_planilla', $tipoPlanilla)->enRango($fechaInicio, $fechaFin));
    }

    public static function obtenerPlanilla(int $mes, int $anio, string $tipoPlanilla, string $orden = 'orden'): Collection
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        return static::queryPlanilla($mes, $anio, $tipoPlanilla)
            ->with(['contratos' => fn($q) => $q->where('tipo_planilla', $tipoPlanilla)->enRango($fechaInicio, $fechaFin)->orderByDesc('fecha_inicio')->limit(1)])
            ->ordenarPor($orden)
            ->get();
    }

    // ─── TOTAL ACTIVOS: solo el count, sin cargar modelos ────────────────────
    public static function totalActivos(int $mes, int $anio, string $tipoPlanilla): int
    {
        return static::queryPlanilla($mes, $anio, $tipoPlanilla)->count();
    }

    public function actualizarOrdenEmpleados(array $empleados): void
    {
        if (empty($empleados)) {
            return;
        }
        $ids = [];
        $cases = [];
        foreach ($empleados as $indice => $empleadoOrden) {
            $id = (int) $empleadoOrden['id'];
            $orden = $indice + 1;
            $ids[] = $id;
            $cases[] = "WHEN id = {$id} THEN {$orden}";
        }
        $casesSql = implode(' ', $cases);
        $idsSql = implode(',', $ids);
        DB::statement("UPDATE plan_empleados SET orden = CASE {$casesSql} END WHERE id IN ({$idsSql})");
    }

    public function obtenerEmpleadoPorUuid($id)
    {
        $empleado = PlanEmpleado::find($id);
        if (!$empleado) {
            throw new Exception("El registro ya no existe");
        }
        return $empleado;
    }

    public function eliminarEmpleado($id)
    {
        $empleado = PlanEmpleado::find($id);
        if (!$empleado) {
            throw new Exception("El registro ya no existe");
        }
        $empleado->delete();
    }

    public function restaurarEmpleado($id)
    {
        $empleado = PlanEmpleado::withTrashed()->where('id', $id)->first();
        if (!$empleado) {
            throw new Exception("El registro ya no existe");
        }
        $empleado->restore();
    }

    /**
     * Lista del panel de empleados. Estado del contrato a hoy: vigente (por defecto), no_vigente (tuvo pero ya no, o aún
     * no inicia), sin (nunca tuvo contrato) o vacío (todos). Cargo, grupo y sistema de pensión se filtran por lo vigente
     * hoy (partículas); tipo de planilla, por el contrato vigente (o cualquiera si no se pide "vigente").
     */
    public function buscarEmpleado(array $filtros = [])
    {
        $query = PlanEmpleado::query()->with(['contratos.asignaciones.grupo', 'contratos.asignaciones.cargo', 'contratos.remuneraciones', 'pensiones']);

        switch ($filtros['estado_contrato'] ?? null) {
            case 'vigente':
            case 'con': // valor antiguo
                $query->conContratoVigente();
                break;
            case 'no_vigente':
                $query->whereHas('contratos')->whereNot(fn($q) => $q->conContratoVigente());
                break;
            case 'sin':
                $query->whereDoesntHave('contratos');
                break;
        }

        if (($filtros['estado_contrato'] ?? null) !== 'sin') {
            if (!empty($filtros['cargo_id'])) {
                PlanillaLaboralConsulta::filtrarEmpleadosPorCargo($query, (int) $filtros['cargo_id']);
            }
            if (!empty($filtros['grupo_codigo'])) {
                PlanillaLaboralConsulta::filtrarEmpleadosPorGrupo($query, $filtros['grupo_codigo'], now());
            }
            if (!empty($filtros['descuento_sp_codigo'])) {
                PlanillaLaboralConsulta::filtrarEmpleadosPorPension($query, $filtros['descuento_sp_codigo']);
            }
            if (!empty($filtros['tipo_planilla'])) {
                $soloVigente = in_array($filtros['estado_contrato'] ?? null, ['vigente', 'con'], true);
                $query->whereHas('contratos', fn($q) => $q->where('tipo_planilla', $filtros['tipo_planilla'])->when($soloVigente, fn($q) => $q->vigentes()));
            }
        }

        $query->buscar($filtros['filtro'] ?? null)->deGenero($filtros['genero'] ?? null);

        if (($filtros['estado'] ?? null) === 'eliminados') {
            $query->onlyTrashed();
        }

        return $query->orderBy('orden')->paginate(20);
    }

    /**
     * Empleados con contrato agrario en el mes (con ese contrato cargado), en el orden configurado.
     * @param array $ordenConfig Ej: [['campo' => 'genero', 'direccion' => 'desc'], ['campo' => 'apellido_paterno', 'direccion' => 'asc']]
     */
    public function obtenerPlanillaAgraria(int $mes, int $anio, array $ordenConfig = [])
    {
        $fechaInicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFinMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
        $contratoDelMes = fn($q) => $q->where('tipo_planilla', 'agraria')->enRango($fechaInicioMes, $fechaFinMes);

        $query = PlanEmpleado::query()
            ->whereNull('deleted_at')
            ->whereHas('contratos', $contratoDelMes)
            ->with(['contratos' => fn($q) => $contratoDelMes($q)->orderByDesc('fecha_inicio')->limit(1)]);

        $this->aplicarOrden($query, $ordenConfig);
        return $query->get();
    }

    /** Orden configurado; si viene vacío, el orden por defecto. */
    protected function aplicarOrden($query, array $ordenConfig): void
    {
        if (empty($ordenConfig)) {
            $query->orderBy('orden');
            return;
        }
        $validos = array_column(self::camposOrdenables(), 'value');
        foreach ($ordenConfig as $item) {
            $campo = $item['campo'] ?? null;
            if ($campo && in_array($campo, $validos, true)) {
                $query->ordenarPor($campo, $item['direccion'] ?? 'asc');
            }
        }
    }

    /** Campos disponibles para armar el orden (los datos personales se ordenan por la persona). */
    public static function camposOrdenables(): array
    {
        return [
            ['value' => 'orden', 'label' => 'Orden (por defecto)'],
            ['value' => 'nombres', 'label' => 'Nombres'],
            ['value' => 'apellido_paterno', 'label' => 'Apellido Paterno'],
            ['value' => 'apellido_materno', 'label' => 'Apellido Materno'],
            ['value' => 'genero', 'label' => 'Género'],
            ['value' => 'documento', 'label' => 'Documento'],
        ];
    }
}
