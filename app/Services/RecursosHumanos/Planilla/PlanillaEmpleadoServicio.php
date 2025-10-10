<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanEmpleado;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanillaEmpleadoServicio
{
    public function obtenerEmpleadoPorUuid($uuid)
    {
        $empleado = PlanEmpleado::where('uuid', $uuid)->first();
        if (!$empleado) {
            throw new Exception("El registro ya no existe");
        }
        return $empleado;
    }

    public function eliminarEmpleado($uuid)
    {
        $empleado = PlanEmpleado::where('uuid', $uuid)->first();
        if (!$empleado) {
            throw new Exception("El registro ya no existe");
        }
        $empleado->delete();
    }
    public function restaurarEmpleado($uuid)
    {
        $empleado = PlanEmpleado::withTrashed()->where('uuid', $uuid)->first();

        if (!$empleado) {
            throw new Exception("El registro ya no existe");
        }

        $empleado->restore();
    }

    /**
     * Registra un nuevo empleado.
     */
    public function registrarEmpleado(array $datos)
    {
        $validados = $this->validarDatos($datos);
        return PlanEmpleado::create($validados);
    }

    /**
     * Actualiza un empleado existente.
     */
    public function actualizarEmpleado(array $datos, $empleadoId)
    {
        $empleado = PlanEmpleado::findOrFail($empleadoId);
        $validados = $this->validarDatos($datos, $empleadoId);
        $empleado->update($validados);
        return $empleado;
    }

    /**
     * Valida los datos de registro o actualización.
     */
    private function validarDatos(array $datos, $empleadoId = null)
    {
        $rules = [
            'nombres' => 'required|string|max:255',
            'documento' => [
                'required',
                'string',
                Rule::unique('plan_empleados', 'documento')->ignore($empleadoId),
            ],
            'fecha_ingreso' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];

        $validator = Validator::make($datos, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validados = $validator->validated();

        // 🔹 Agregar campos adicionales (manuales o automáticos)
        $adicionales = [
            'apellido_paterno' => $datos['apellido_paterno'] ?? null,
            'apellido_materno' => $datos['apellido_materno'] ?? null,
            'email' => $datos['email'] ?? null,
            'numero' => $datos['numero'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'genero' => $datos['genero'] ?? null,
            'comentarios' => $datos['comentarios'] ?? null,
            'orden' => $datos['orden'] ?? null,
        ];

        // 🔹 Retornar mezcla entre validados y adicionales
        return array_merge($validados, $adicionales);
    }
    public function buscarEmpleado(array $filtros = [])
    {
        $query = PlanEmpleado::query()
            ->with([
                'contratos' => function ($q) {
                    $q->orderByDesc('fecha_inicio')->limit(1);
                }
            ]);

        // 🔹 Determinar si hay filtros de contrato activos
        $filtrosContrato = collect([
            'cargo_id',
            'descuento_sp_codigo',
            'grupo_codigo',
            'tipo_planilla',
        ])->filter(fn($key) => !empty($filtros[$key]));

        // 🔹 Si hay filtros de contrato -> usar whereHas
        if ($filtrosContrato->isNotEmpty()) {
            $query->whereHas('contratos', function ($q) use ($filtros) {
                if (!empty($filtros['cargo_id'])) {
                    $q->where('cargo_codigo', $filtros['cargo_id']);
                }

                if (!empty($filtros['descuento_sp_codigo'])) {
                    $q->whereHas('descuento', function ($sub) use ($filtros) {
                        $sub->where('codigo', $filtros['descuento_sp_codigo']);
                    });
                }

                if (!empty($filtros['grupo_codigo'])) {
                    $q->where('grupo_codigo', $filtros['grupo_codigo']);
                }

                if (!empty($filtros['tipo_planilla'])) {
                    $q->where('tipo_planilla', $filtros['tipo_planilla']);
                }
            });
        }

        // 🔹 Filtros propios de PlanEmpleado
        if (!empty($filtros['filtro'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('nombres', 'like', "%{$filtros['filtro']}%")
                    ->orWhere('apellido_paterno', 'like', "%{$filtros['filtro']}%")
                    ->orWhere('apellido_materno', 'like', "%{$filtros['filtro']}%")
                    ->orWhere('documento', 'like', "%{$filtros['filtro']}%");
            });
        }

        if (!empty($filtros['genero'])) {
            $query->where('genero', $filtros['genero']);
        }

        // 🔹 Estado (activos o eliminados)
        if (!empty($filtros['estado'])) {
            if ($filtros['estado'] === 'eliminados') {
                $query->onlyTrashed();
            }
        }

        // 🔹 Ordenar y paginar
        return $query->orderBy('orden')->paginate(20);
    }

    /**
     * Retorna los empleados con contrato agrario vigente en el mes/año indicado.
     */
    public function obtenerPlanillaAgraria(int $mes, int $anio)
    {
        $fechaInicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFinMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        return PlanEmpleado::query()
            ->whereNull('deleted_at') // solo empleados activos
            ->whereHas('contratos', function ($query) use ($fechaInicioMes, $fechaFinMes) {
                $query->where('tipo_planilla', 'agraria')
                    ->where('fecha_inicio', '<=', $fechaFinMes)
                    ->where(function ($q) use ($fechaInicioMes) {
                        $q->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $fechaInicioMes);
                    });
            })
            ->with([
                'contratos' => function ($query) use ($fechaInicioMes, $fechaFinMes) {
                    $query->where('tipo_planilla', 'agraria')
                        ->where('fecha_inicio', '<=', $fechaFinMes)
                        ->where(function ($q) use ($fechaInicioMes) {
                            $q->whereNull('fecha_fin')
                                ->orWhere('fecha_fin', '>=', $fechaInicioMes);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->limit(1); // solo el contrato más reciente
                }
            ])
            ->orderBy('nombres')
            ->get();
    }
}