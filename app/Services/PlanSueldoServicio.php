<?php

namespace App\Services;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Models\PlanSueldo;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanSueldoServicio
{
    /**
     * Obtiene la colección de empleados que carecen de sueldo activo en el mes/año.
     */
    public function obtenerEmpleadosSinSueldoEnPeriodo(int $mes, int $anio): Collection
    {
        $empleadosServicio = app(PlanillaEmpleadoServicio::class);
        $empleados = $empleadosServicio->obtenerPlanillaAgraria($mes, $anio);

        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth()->format('Y-m-d');
        $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d');

        // Cargar relación 'sueldos' filtrada por el periodo
        $empleados->load([
            'sueldos' => function ($q) use ($inicioMes, $finMes) {
                $q->where('fecha_inicio', '<=', $finMes)
                    ->where(function ($sub) use ($inicioMes) {
                        $sub->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $inicioMes);
                    });
            }
        ]);

        return $empleados->filter(fn($emp) => $emp->sueldos->isEmpty());
    }
    /**
     * Registra un nuevo sueldo cerrando el anterior activo de forma automática.
     *
     * @param array $datos ['plan_empleado_id', 'sueldo', 'fecha_inicio']
     * @return PlanSueldo
     * @throws Exception
     */
    public function crear(array $datos): PlanSueldo
    {
        return DB::transaction(function () use ($datos) {
            $empleadoId = $datos['plan_empleado_id'];
            $sueldoMonto = (float) $datos['sueldo'];
            $fechaInicioNuevo = Carbon::parse($datos['fecha_inicio'])->startOfDay();
            $fechaInicioStr = $fechaInicioNuevo->format('Y-m-d');

            if ($sueldoMonto <= 0) {
                throw new Exception("El sueldo debe ser mayor a 0.00.");
            }

            // 1. Validar que no exista un sueldo con exactamente la misma fecha de inicio
            $existeMismaFecha = PlanSueldo::where('plan_empleado_id', $empleadoId)
                ->where('fecha_inicio', $fechaInicioStr)
                ->exists();

            if ($existeMismaFecha) {
                throw new Exception("Ya existe un sueldo registrado exactamente en la fecha {$fechaInicioStr} para este empleado.");
            }

            // 2. Buscar si existe un sueldo posterior (el inmediatamente siguiente en la línea de tiempo)
            $sueldoSiguiente = PlanSueldo::where('plan_empleado_id', $empleadoId)
                ->where('fecha_inicio', '>', $fechaInicioStr)
                ->orderBy('fecha_inicio', 'asc')
                ->first();

            // 3. Buscar el sueldo inmediatamente anterior (el previo en la línea de tiempo)
            $sueldoAnterior = PlanSueldo::where('plan_empleado_id', $empleadoId)
                ->where('fecha_inicio', '<', $fechaInicioStr)
                ->orderBy('fecha_inicio', 'desc')
                ->first();

            // Determinar la fecha_fin del nuevo sueldo
            $fechaFinNuevo = null;

            if ($sueldoSiguiente) {
                // Si hay un sueldo en el futuro, el nuevo sueldo debe terminar 1 día antes
                $fechaFinNuevo = Carbon::parse($sueldoSiguiente->fecha_inicio)->subDay()->format('Y-m-d');
            }

            // 4. Si hay un sueldo anterior, debemos ajustar su fecha_fin
            if ($sueldoAnterior) {
                $nuevaFechaFinAnterior = $fechaInicioNuevo->copy()->subDay()->format('Y-m-d');

                // Solo actualizamos si el sueldo anterior no tenía fecha_fin o si su fecha_fin actual era mayor
                if (is_null($sueldoAnterior->fecha_fin) || $sueldoAnterior->fecha_fin > $nuevaFechaFinAnterior) {
                    $sueldoAnterior->update([
                        'fecha_fin' => $nuevaFechaFinAnterior
                    ]);
                }
            }

            // 5. Crear el nuevo sueldo intercalado o final
            return PlanSueldo::create([
                'plan_empleado_id' => $empleadoId,
                'sueldo' => $sueldoMonto,
                'fecha_inicio' => $fechaInicioStr,
                'fecha_fin' => $fechaFinNuevo,
            ]);
        });
    }

    /**
     * Registro masivo desde el componente de validación
     */
    public function crearMasivo(array $sueldos, array $fechas): int
    {
        $guardados = 0;

        DB::transaction(function () use ($sueldos, $fechas, &$guardados) {
            foreach ($sueldos as $empleadoId => $monto) {
                // Solo procesa si ambos valores existen y son válidos
                if (!empty($monto) && !empty($fechas[$empleadoId])) {
                    $this->crear([
                        'plan_empleado_id' => $empleadoId,
                        'sueldo' => $monto,
                        'fecha_inicio' => $fechas[$empleadoId],
                    ]);
                    $guardados++;
                }
            }
        });

        return $guardados;
    }

    /**
     * Obtiene el último sueldo vigente para una lista de empleados.
     */
    public function obtenerSueldosPorMes($mes, $anio)
    {
        // Fecha límite para validar sueldos activos
        $fechaLimite = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        // 1. Traer todos los empleados del mes
        $empleados = PlanMensualDetalle::whereHas('planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)->where('anio', $anio);
        })
            ->get(['plan_empleado_id', 'nombres', 'documento']);

        if ($empleados->isEmpty()) {
            throw new Exception("No existen empleados registrados en la planilla mensual de {$mes}/{$anio}.");
        }

        $empleadoIds = $empleados->pluck('plan_empleado_id')->toArray();

        // 2. Sueldos por empleado
        $sueldos = PlanSueldo::whereIn('plan_empleado_id', $empleadoIds)
            ->where('fecha_inicio', '<=', $fechaLimite)
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->groupBy('plan_empleado_id')
            ->map(function ($historial) {
                return (float) $historial->first()->sueldo;
            });

        // 3. Validación: que todos tengan sueldo
        foreach ($empleados as $emp) {
            if (!$sueldos->has($emp->plan_empleado_id)) {

                //buscar primero si el id existe:
                $empleado = PlanEmpleado::find($emp->plan_empleado_id);
                if (!$empleado) {
                    throw new Exception(
                        "ERROR CRÍTICO: El empleado {$emp->nombres} ({$emp->documento}) " .
                        "ya no esta registrado con el mismo identificador, debe reasignar id"
                    );
                }
                throw new Exception(
                    "ERROR CRÍTICO: El empleado {$emp->nombres} ({$emp->documento}) " .
                    "no tiene un sueldo registrado vigente al {$fechaLimite->format('d/m/Y')}. " .
                    "Debe registrar un sueldo en la tabla plan_sueldos."
                );
            }
        }

        return $sueldos->toArray();
    }

    public function listar()
    {
        return PlanSueldo::all();
    }

    public function obtenerPorId($id)
    {
        return PlanSueldo::find($id);
    }



    public function actualizar($id, array $datos)
    {
        $planSueldo = PlanSueldo::find($id);
        if ($planSueldo) {
            $planSueldo->update($datos);
        }
        return $planSueldo;
    }

    public function eliminar($id)
    {
        $planSueldo = PlanSueldo::find($id);

        if (!$planSueldo) {
            return false;
        }

        DB::beginTransaction();
        try {
            $empleadoId = $planSueldo->plan_empleado_id;

            // Eliminar el sueldo actual
            $planSueldo->delete();

            // Buscar el último sueldo anterior
            $ultimoSueldoAnterior = PlanSueldo::where('plan_empleado_id', $empleadoId)
                ->orderByDesc('fecha_inicio')
                ->first();

            if ($ultimoSueldoAnterior) {
                $ultimoSueldoAnterior->update(['fecha_fin' => null]);
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

}