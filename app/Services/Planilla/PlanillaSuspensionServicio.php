<?php

namespace App\Services\Planilla;

use App\Models\PlanSuspension;
use Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanillaSuspensionServicio
{
    public static function obtenerSuspensiones($mes, $anio)
    {
        // Rango del mes con Carbon puro (sin toDate())
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay(); 
        $finMes    = Carbon::create($anio, $mes, 1)->endOfMonth()->startOfDay();

        // Obtener suspensiones que se cruzan con el mes
        $suspensiones = PlanSuspension::where(function ($q) use ($inicioMes, $finMes) {
                $q->whereBetween('fecha_inicio', [$inicioMes, $finMes])
                  ->orWhereBetween('fecha_fin', [$inicioMes, $finMes])
                  ->orWhere(function ($q2) use ($inicioMes, $finMes) {
                      $q2->where('fecha_inicio', '<=', $inicioMes)
                         ->where('fecha_fin', '>=', $finMes);
                  });
        })->get();

        $diasPorEmpleado = [];

        foreach ($suspensiones as $s) {

            // Normalizar solo fecha, pero siempre Carbon
            $fInicio = Carbon::parse($s->fecha_inicio)->startOfDay();
            $fFin    = Carbon::parse($s->fecha_fin)->startOfDay();

            // Ajustar al mes
            $inicio = $fInicio->lt($inicioMes) ? $inicioMes : $fInicio;
            $fin    = $fFin->gt($finMes) ? $finMes : $fFin;

            if ($inicio->lte($fin)) {
                // Dif. en días enteros siempre correcta
                $dias = $inicio->diffInDays($fin) + 1;

                $diasPorEmpleado[$s->plan_empleado_id] =
                    ($diasPorEmpleado[$s->plan_empleado_id] ?? 0) + $dias;
            }
        }

        return $diasPorEmpleado;
    }

    /**
     * Crear una nueva suspensión.
     *
     * @param array $datos
     * @return PlanSuspension
     */
    public function crear(array $datos): PlanSuspension
    {
        return PlanSuspension::create([
            'plan_empleado_id' => $datos['plan_empleado_id'],
            'tipo_suspension_id' => $datos['tipo_suspension_id'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'documento_respaldo' => $datos['documento_respaldo'] ?? null,
            'creado_por' => Auth::id(),
        ]);
    }

    /**
     * Leer una suspensión por ID.
     *
     * @param int $id
     * @return PlanSuspension|null
     */
    public function leer(int $id): ?PlanSuspension
    {
        return PlanSuspension::with(['empleado', 'tipoSuspension'])->find($id);
    }

    /**
     * Listar todas las suspensiones con filtros opcionales.
     *
     * @param array $filtros
     * @return Collection
     */
    public function listar(array $filtros = []): Collection
    {
        $query = PlanSuspension::with(['empleado', 'tipoSuspension']);

        $mes = $filtros['mes'] ?? null;
        $anio = $filtros['anio'] ?? null;

        // Año + Mes → filtrar por un mes específico
        if ($anio && $mes) {
            $query->delMes($mes, $anio);
        }
        // Solo Año → filtrar por todo el año
        else if ($anio) {
            $query->whereYear('fecha_inicio', $anio);
        }
        // Solo Mes (caso raro) → se ignora, porque sin año NO tiene sentido
        // o podrías implementar un comportamiento específico, pero lo normal es ignorarlo.

        return $query->orderBy('plan_empleado_id')
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }

    /**
     * Listar suspensiones por mes y año específico.
     *
     * @param int $mes
     * @param int $anio
     * @return Collection
     */
    public function listarPorMes(?int $mes, ?int $anio): Collection
    {
        $filtros = [];

        if (!is_null(value: $mes)) {
            $filtros['mes'] = $mes;
        }

        if (!is_null($anio)) {
            $filtros['anio'] = $anio;
        }

        return $this->listar($filtros);
    }

    /**
     * Actualizar una suspensión existente.
     *
     * @param int $id
     * @param array $datos
     * @return PlanSuspension
     */
    public function actualizar(int $id, array $datos): PlanSuspension
    {
        $suspension = PlanSuspension::findOrFail($id);

        $suspension->update([
            'plan_empleado_id' => $datos['plan_empleado_id'] ?? $suspension->plan_empleado_id,
            'tipo_suspension_id' => $datos['tipo_suspension_id'] ?? $suspension->tipo_suspension_id,
            'fecha_inicio' => $datos['fecha_inicio'] ?? $suspension->fecha_inicio,
            'fecha_fin' => $datos['fecha_fin'] ?? $suspension->fecha_fin,
            'observaciones' => $datos['observaciones'] ?? $suspension->observaciones,
            'documento_respaldo' => $datos['documento_respaldo'] ?? $suspension->documento_respaldo,
            'actualizado_por' => Auth::id(),
        ]);

        return $suspension->fresh();
    }

    /**
     * Eliminar una suspensión (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        $suspension = PlanSuspension::findOrFail($id);
        return $suspension->delete();
    }

    /**
     * Eliminar múltiples suspensiones.
     *
     * @param array $ids
     * @return int Cantidad de registros eliminados
     */
    public function eliminarMultiples(array $ids): int
    {
        return PlanSuspension::whereIn('id', $ids)->delete();
    }

    /**
     * Obtener suspensiones activas de un empleado.
     *
     * @param int $empleadoId
     * @return Collection
     */
    public function obtenerActivasPorEmpleado(int $empleadoId): Collection
    {
        return PlanSuspension::where('plan_empleado_id', $empleadoId)
            ->activas()
            ->with('tipoSuspension')
            ->get();
    }

    /**
     * Verificar si existe solapamiento con otras suspensiones.
     *
     * @param int $empleadoId
     * @param string $fechaInicio
     * @param string|null $fechaFin
     * @param int|null $exceptoId
     * @return bool
     */
    public function existeSolapamiento(
        int $empleadoId,
        string $fechaInicio,
        ?string $fechaFin,
        ?int $exceptoId = null
    ): bool {
        $query = PlanSuspension::where('plan_empleado_id', $empleadoId)
            ->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin ?? now()])
                    ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin ?? now()])
                    ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                        $q2->where('fecha_inicio', '<=', $fechaInicio)
                            ->where(function ($q3) use ($fechaFin) {
                                $q3->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $fechaFin ?? now());
                            });
                    });
            });

        if ($exceptoId) {
            $query->where('id', '!=', $exceptoId);
        }

        return $query->exists();
    }

    /**
     * Finalizar una suspensión en una fecha específica.
     *
     * @param int $id
     * @param string|null $fechaFin
     * @return PlanSuspension
     */
    public function finalizar(int $id, ?string $fechaFin = null): PlanSuspension
    {
        $suspension = PlanSuspension::findOrFail($id);
        $suspension->finalizar($fechaFin);
        $suspension->actualizado_por = Auth::id();
        $suspension->save();

        return $suspension->fresh();
    }

    /**
     * Obtener IDs de suspensiones del mes actual en la base de datos.
     *
     * @param int $mes
     * @param int $anio
     * @return array
     */
    public function obtenerIdsDelMes(int $mes, int $anio): array
    {
        return PlanSuspension::delMes($mes, $anio)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Preparar datos para Handsontable.
     *
     * @param int $mes
     * @param int $anio
     * @return array
     */
    public function prepararParaHandsontable(?int $mes, ?int $anio): array
    {
        return $this->listarPorMes($mes, $anio)
            ->map(function ($suspension) {
                return [
                    'id' => $suspension->id,
                    'plan_empleado_id' => $suspension->plan_empleado_id,
                    'tipo_suspension_id' => $suspension->tipo_suspension_id,
                    'fecha_inicio' => $suspension->fecha_inicio->format('Y-m-d'),
                    'fecha_fin' => $suspension->fecha_fin?->format('Y-m-d'),
                    'observaciones' => $suspension->observaciones,
                    'duracion_dias' => $suspension->duracion_dias
                ];
            })
            ->toArray();
    }
}