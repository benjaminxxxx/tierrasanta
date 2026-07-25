<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Support\ExcelHelper;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContratoServicio
{
    public function historial(int $empleadoId)
    {
        return PlanContrato::where('plan_empleado_id', $empleadoId)
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public function contratoVigente(int $empleadoId): ?PlanContrato
    {
        return PlanContrato::where('plan_empleado_id', $empleadoId)
            ->whereIn('estado', ['activo', 'en_prueba'])
            ->first();
    }

    protected function validarSolapamiento(int $empleadoId, Carbon $fechaInicio, ?int $excluirContratoId = null): void
    {
        $ultimoCerrado = PlanContrato::where('plan_empleado_id', $empleadoId)
            ->where('estado', 'finalizado')
            ->when($excluirContratoId, fn($q) => $q->where('id', '!=', $excluirContratoId))
            ->orderByDesc('fecha_fin')
            ->first();

        if ($ultimoCerrado && $fechaInicio->lte($ultimoCerrado->fecha_fin)) {
            throw new \DomainException(
                "La fecha de inicio debe ser posterior al {$ultimoCerrado->fecha_fin->format('d/m/Y')}, "
                . 'fecha en que finalizó el contrato anterior.'
            );
        }
    }

    public function guardarContrato(array $data, ?int $contratoId = null, ?int $fila = null): PlanContrato
    {
        $validator = Validator::make($data, [
            'plan_empleado_id' => ['required', 'integer', 'exists:plan_empleados,id'],
            'fecha_inicio' => ['required', 'date'],
            'tipo_planilla' => ['required'],
            'plan_sp_codigo' => ['nullable'],
            'tipo_contrato' => ['required'],
            'modalidad_pago' => ['required'],
            'grupo_codigo' => ['nullable'],
        ], [
            'plan_empleado_id.required' => 'El empleado es obligatorio.',
            'plan_empleado_id.exists' => 'El empleado seleccionado no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'tipo_planilla.required' => 'El tipo de planilla es obligatorio.',
            'tipo_contrato.required' => 'El tipo de contrato es obligatorio.',
            'modalidad_pago.required' => 'La modalidad de pago es obligatoria.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $planEmpleadoId = $data['plan_empleado_id'];
        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = !empty($data['fecha_fin']) ? Carbon::parse($data['fecha_fin']) : null;
        $data['grupo_codigo'] = blank($data['grupo_codigo'] ?? null) ? null : $data['grupo_codigo'];

        // Consistencia de fechas
        if ($fechaFin && $fechaFin->lt($fechaInicio)) {
            $mensaje = $fila
                ? "Error en la fila {$fila}: la fecha de fin no puede ser anterior a la de inicio."
                : 'La fecha de fin no puede ser anterior a la de inicio.';

            throw ValidationException::withMessages(['fecha_fin' => $mensaje]);
        }

        // Solo un contrato abierto (activo/en_prueba) por empleado
        if (!$contratoId) {
            $tieneAbierto = PlanContrato::where('plan_empleado_id', $planEmpleadoId)
                ->whereIn('estado', ['activo', 'en_prueba'])
                ->exists();

            if ($tieneAbierto) {
                throw ValidationException::withMessages([
                    'plan_empleado_id' => 'El empleado ya tiene un contrato vigente. Debe finalizarlo antes de crear uno nuevo.',
                ]);
            }
        }

        // Solapamiento de fechas contra el resto del historial
        $this->validarTraslapeFechas($planEmpleadoId, $fechaInicio, $fechaFin, $contratoId);

        return DB::transaction(function () use ($data, $contratoId) {
            $userId = auth()->id();

            if ($contratoId) {
                $contrato = PlanContrato::findOrFail($contratoId);
                $contrato->update(array_merge($data, ['actualizado_por' => $userId]));
                return $contrato->fresh();
            }

            $data['estado'] = $data['estado'] ?? 'activo';

            return PlanContrato::create(array_merge($data, ['creado_por' => $userId]));
        });
    }

    public function finalizarContrato(int $contratoId, array $datosCierre): PlanContrato
    {
        $contrato = PlanContrato::findOrFail($contratoId);

        if ($contrato->estado === 'finalizado') {
            throw new \DomainException('Este contrato ya está finalizado.');
        }

        $validator = Validator::make($datosCierre, [
            'fecha_fin' => ['required', 'date'],
            'motivo_cese_sunat' => ['required', 'string'],
        ], [
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'motivo_cese_sunat.required' => 'El motivo de cese es obligatorio.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $fechaFin = Carbon::parse($datosCierre['fecha_fin']);

        if ($fechaFin->lt($contrato->fecha_inicio)) {
            throw new \DomainException('La fecha de fin no puede ser anterior a la fecha de inicio del contrato.');
        }

        if ($fechaFin->gt(now())) {
            throw new \DomainException('No se puede finalizar un contrato con una fecha futura.');
        }

        $contrato->update([
            'fecha_fin' => $fechaFin,
            'motivo_cese_sunat' => $datosCierre['motivo_cese_sunat'],
            'comentario_cese' => $datosCierre['comentario_cese'] ?? null,
            'estado' => 'finalizado',
            'finalizado_por' => auth()->id(),
        ]);

        return $contrato->fresh();
    }

    public function reabrirContrato(int $contratoId): PlanContrato
    {
        $contrato = PlanContrato::findOrFail($contratoId);

        if ($contrato->estado !== 'finalizado') {
            throw new \DomainException('Este contrato no está finalizado.');
        }

        $masReciente = PlanContrato::where('plan_empleado_id', $contrato->plan_empleado_id)
            ->orderByDesc('fecha_inicio')
            ->first();

        if ($masReciente->id !== $contrato->id) {
            throw new \DomainException('Solo se puede reaperturar el contrato más reciente del historial.');
        }

        $contrato->update([
            'fecha_fin' => null,
            'motivo_cese_sunat' => null,
            'comentario_cese' => null,
            'finalizado_por' => null,
            'estado' => $contrato->fecha_fin_prueba && $contrato->fecha_fin_prueba->isFuture() ? 'en_prueba' : 'activo',
        ]);

        return $contrato->fresh();
    }
    /**
     * Obtiene todos los contratos vigentes durante un mes/año.
     */
    public static function obtenerContratosVigentes(int $mes, int $anio)
    {
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        return PlanContrato::whereDate('fecha_inicio', '<=', $finMes)
            ->where(function ($q) use ($inicioMes) {
                $q->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $inicioMes);
            })
            ->get()
            ->keyBy('plan_empleado_id');
        // puedes keyBy('id') si prefieres, pero normalmente se usa empleado
    }
    public function importarContratos($file)
    {
        $dataExcel = app(ImportContratoServicio::class)->importarContratos($file);
        ValidarContratoServicio::validarDatosExcel($dataExcel);
        DB::transaction(function () use ($dataExcel) {
            foreach ($dataExcel as $indice => $registro) {
                $registroEmpleado = PlanEmpleado::where('documento', $registro['dni'])->first();
                if (!$registroEmpleado) {
                    continue; // O manejar el error según sea necesario
                }

                $fecha_fin = $registro['fecha_baja'] ? ExcelHelper::parseFechaExcel($registro['fecha_baja']) : null;
                $estado = $registro['estado'] === 'BAJA' ? 'finalizado' : 'activo';

                $this->guardarContrato([
                    'plan_empleado_id' => $registroEmpleado->id,
                    'fecha_inicio' => ExcelHelper::parseFechaExcel($registro['fecha_ingreso']),
                    'cargo_codigo' => null,
                    'tipo_planilla' => $registro['planilla'],
                    'plan_sp_codigo' => $registro['sistema'],
                    'tipo_contrato' => 'indefinido',
                    'modalidad_pago' => 'mensual',
                    'grupo_codigo' => null,
                    'fecha_fin' => $fecha_fin,
                    'estado' => $estado,
                ], null, $indice + 1);
            }
        });
    }
    /**
     * Guarda o actualiza un contrato con validaciones de negocio.
     */
    /*
    public function guardarContrato(array $data, $contratoId = null, $fila = null)
    {
        $tieneActivo = PlanContrato::where('plan_empleado_id', $data['plan_empleado_id'])
            ->where('estado', 'activo')
            ->when($contratoId, fn($q) => $q->where('id', '!=', $contratoId))
            ->exists();

        if ($tieneActivo) {
            throw new Exception("No se puede proceder: El empleado aún tiene un contrato activo.");
        }

        $validator = Validator::make($data, [
            'fecha_inicio' => [
                'required',
                'date'
            ],
            'tipo_planilla' => 'required',
            'plan_sp_codigo' => 'required',
            'tipo_contrato' => 'required',
            'modalidad_pago' => 'required',
            'grupo_codigo' => 'nullable',
        ], [
            'plan_empleado_id.required' => 'El empleado es obligatorio.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'tipo_planilla.required' => 'El tipo de planilla es obligatorio.',
            'plan_sp_codigo.required' => 'El plan SP es obligatorio.',
            'tipo_contrato.required' => 'El tipo de contrato es obligatorio.',
            'modalidad_pago.required' => 'La modalidad de pago es obligatoria.',

        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $plan_empleado_id = $data['plan_empleado_id'];
        $fecha_inicio = Carbon::parse($data['fecha_inicio']);
        $fecha_fin = isset($data['fecha_fin']) ? Carbon::parse($data['fecha_fin']) : null;
        $data['grupo_codigo'] = $data['grupo_codigo'] == '' ? null : $data['grupo_codigo'];

        // 1. Validar que no existan contratos "Activos" (si es nuevo)
        if (!$contratoId) {
            $tieneActivo = PlanContrato::where('plan_empleado_id', $plan_empleado_id)
                ->where('estado', 'activo')
                ->exists();

            if ($tieneActivo) {
                throw ValidationException::withMessages([
                    'plan_empleado_id' => 'El empleado ya tiene un contrato activo. Debe finalizarlo antes de crear uno nuevo.'
                ]);
            }
        }

        // 2. Validar solapamiento de fechas (Overlap)
        $this->validarTraslapeFechas($plan_empleado_id, $fecha_inicio, $fecha_fin, $contratoId);

        // 3. Validar consistencia de fechas
        if ($fecha_fin && $fecha_fin->lt($fecha_inicio)) {
            $message = $contratoId
                ? 'La fecha de fin no puede ser anterior a la de inicio.'
                : "Error en la fila {$fila}: La fecha de fin no puede ser anterior a la de inicio.";
            throw ValidationException::withMessages(['fecha_fin' => $message]);
        }

        return DB::transaction(function () use ($data, $contratoId) {
            $userId = auth()->id();

            if ($contratoId) {
                $contrato = PlanContrato::findOrFail($contratoId);
                $contrato->update(array_merge($data, ['actualizado_por' => $userId]));
                return $contrato;
            }

            return PlanContrato::create(array_merge($data, ['creado_por' => $userId]));
        });
    }*/

    /**
     * Finaliza un contrato exigiendo datos de cese.
     */
    /*
    public function finalizarContrato($contratoId, array $data)
    {
        $contrato = PlanContrato::findOrFail($contratoId);
        $fecha_fin = Carbon::parse($data['fecha_fin']);

        if ($fecha_fin->lt($contrato->fecha_inicio)) {
            throw ValidationException::withMessages(['fecha_fin' => 'La fecha de cese no puede ser anterior al inicio del contrato.']);
        }

        if (empty($data['motivo_cese_sunat'])) {
            throw ValidationException::withMessages(['motivo_cese_sunat' => 'El motivo de cese SUNAT es obligatorio para finalizar.']);
        }

        $contrato->update([
            'fecha_fin' => $fecha_fin,
            'motivo_cese_sunat' => $data['motivo_cese_sunat'],
            'comentario_cese' => $data['comentario_cese'] ?? null,
            'estado' => 'finalizado',
            'finalizado_por' => auth()->id()
        ]);

        return $contrato;
    }*/

    /**
     * Lista contratos con filtros y paginación opcional.
     */
    public function listarContratos(array $filtros = [], $perPage = null)
    {
        $query = PlanContrato::query()
            ->with(['empleado', 'grupo']);

        // Filtros de relación (Empleado)
        if (!empty($filtros['buscar'])) {
            $buscar = $filtros['buscar'];
            $query->whereHas('empleado', function ($q) use ($buscar) {
                $q->where(function ($sq) use ($buscar) {
                    $sq->where('nombres', 'like', "%$buscar%")
                        ->orWhere('apellido_paterno', 'like', "%$buscar%")
                        ->orWhere('apellido_materno', 'like', "%$buscar%")
                        ->orWhere('documento', 'like', "%$buscar%");
                });
            });
        }

        // Filtros directos (Strings/Enums)
        $query->when(!empty($filtros['estado']), fn($q) => $q->where('estado', $filtros['estado']))
            ->when(!empty($filtros['tipo_planilla']), fn($q) => $q->where('tipo_planilla', $filtros['tipo_planilla']))
            ->when(!empty($filtros['cargo_codigo']), fn($q) => $q->where('cargo_codigo', $filtros['cargo_codigo']))
            ->when(!empty($filtros['grupo_codigo']), fn($q) => $q->where('grupo_codigo', $filtros['grupo_codigo']));

        // Filtros de Rango de Fechas
        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_inicio', '<=', $filtros['fecha_hasta']);
        }

        $query->orderBy('created_at', 'desc');

        return $perPage ? $query->paginate($perPage) : $query->get();
    }
    public function eliminarContrato($contratoId)
    {
        $contrato = PlanContrato::findOrFail($contratoId);
        $contrato->update(['eliminado_por' => auth()->id()]);
        return $contrato->delete();
    }

    /**
     * Valida solapamiento de fechas contra el resto del historial del empleado.
     * Compara rangos reales, no solo el último cerrado — cubre también el caso
     * de edición, donde se puede estar moviendo la fecha de un contrato intermedio.
     */
    protected function validarTraslapeFechas(int $empleadoId, Carbon $fechaInicio, ?Carbon $fechaFin, ?int $contratoId = null): void
    {
        $finComparable = $fechaFin ?? Carbon::create(9999, 12, 31); // contrato abierto = "hasta el infinito"

        $traslape = PlanContrato::where('plan_empleado_id', $empleadoId)
            ->when($contratoId, fn($q) => $q->where('id', '!=', $contratoId))
            ->where(function ($q) use ($fechaInicio, $finComparable) {
                $q->where('fecha_inicio', '<=', $finComparable)
                    ->where(function ($q2) use ($fechaInicio) {
                        $q2->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $fechaInicio);
                    });
            })
            ->first();

        if ($traslape) {
            $rango = $traslape->fecha_fin
                ? "{$traslape->fecha_inicio->format('d/m/Y')} - {$traslape->fecha_fin->format('d/m/Y')}"
                : "{$traslape->fecha_inicio->format('d/m/Y')} - actualidad";

            throw ValidationException::withMessages([
                'fecha_inicio' => "Las fechas se solapan con otro contrato del empleado ({$rango}).",
            ]);
        }
    }


}