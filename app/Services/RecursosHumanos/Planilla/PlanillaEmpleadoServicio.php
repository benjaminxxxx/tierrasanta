<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanEmpleado;
use App\Services\Configuracion\ConfiguracionHistorialServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanillaEmpleadoServicio
{
    public static function obtenerAsignacionesFamiliares(int $mes, int $anio)
    {
        // 1. Fecha de corte
        $fechaCorte = Carbon::create($anio, $mes, 1)->endOfMonth();

        // 2. Obtener valor de asignación familiar vigente ese mes
        $valor = ConfiguracionHistorialServicio::valorVigente('asignacion_familiar', $mes, $anio);

        // 3. Cargar empleados + familiares
        $empleados = PlanEmpleado::with('asignacionFamiliar')->get();

        // 4. Filtrar empleados que califican
        $result = $empleados->filter(function ($empleado) use ($fechaCorte) {

            foreach ($empleado->asignacionFamiliar as $familiar) {
                $edad = Carbon::parse($familiar->fecha_nacimiento)->diffInYears($fechaCorte);

                // Condiciones de ley
                if ($edad < 18) {
                    return true;
                }

                if ($edad >= 18 && $familiar->esta_estudiando) {
                    return true;
                }
            }

            return false; // ninguno califica
        })
        ->keyBy('id')
        ->map(fn () => $valor);

        return $result; 
    }
    public function actualizarOrdenEmpleados(array $empleados): void
    {
        if (empty($empleados))
            return;

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

        DB::statement("
                UPDATE plan_empleados
                SET orden = CASE {$casesSql} END
                WHERE id IN ({$idsSql})
            ");
    }

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
    public function guardarPorDocumento(array $datos)
    {
        if (empty($datos['documento'])) {
            throw new Exception('El documento (DNI) es obligatorio.');
        }

        $empleado = PlanEmpleado::where('documento', $datos['documento'])->first();

        if ($empleado) {
            return $this->actualizarEmpleado($datos, $empleado->id);
        }

        return $this->registrarEmpleado($datos);
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
                'digits:8',
                Rule::unique('plan_empleados', 'documento')->ignore($empleadoId),
            ],
            'genero' => 'nullable|in:M,F',
            'email' => ['nullable', 'email'],
            'fecha_ingreso' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];
        $messages = [

            // --- Reglas generales ---
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser un texto válido.',
            'max' => 'El campo :attribute no debe exceder los :max caracteres.',
            'date_format' => 'El campo :attribute debe tener el formato YYYY-MM-DD.',
            'before' => 'El campo :attribute debe ser una fecha anterior a hoy.',
            'before_or_equal' => 'El campo :attribute no puede ser una fecha futura.',
            'unique' => 'El valor del campo :attribute ya existe en el sistema.',


            // --- Campos específicos ---
            'nombres.required' => 'Los nombres del empleado son obligatorios.',
            'nombres.string' => 'Los nombres del empleado deben ser texto.',
            'genero.in' => 'El género solo acepta como valores M o F',
            'email.email' => 'El Email debe tener un formato válido',

            'documento.digits' => 'El DNI debe contener exactamente 8 dígitos numéricos.',
            'documento.required' => 'El DNI del empleado es obligatorio.',
            'documento.string' => 'El DNI debe ser un texto válido.',
            'documento.unique' => 'El DNI ya se encuentra registrado en otro empleado.',

            'fecha_ingreso.date_format' => 'La fecha de ingreso debe tener el formato YYYY-MM-DD.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser posterior a hoy.',

            'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe tener el formato YYYY-MM-DD.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a la fecha actual.',
        ];

        $validator = Validator::make($datos, $rules, $messages);

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

        if (!empty($filtros['estado_contrato'])) {

            if ($filtros['estado_contrato'] === 'con') {
                $query->whereHas('contratos', function ($q) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', now());
                });
            }

            if ($filtros['estado_contrato'] === 'sin') {
                $query->whereDoesntHave('contratos');
            }
        }

        $filtrosContrato = collect([
            'cargo_id',
            'descuento_sp_codigo',
            'grupo_codigo',
            'tipo_planilla',
        ])->filter(fn($key) => !empty($filtros[$key]));

        if (
            $filtrosContrato->isNotEmpty()
            && $filtros['estado_contrato'] !== 'sin'
        ) {
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
    public function obtenerPlanillaAgraria(int $mes, int $anio, $orden = "orden")
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
            ->orderBy($orden)
            ->get();
    }
}