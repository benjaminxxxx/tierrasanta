<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContratoServicio
{
    /**
     * Registra un nuevo contrato para un empleado, finalizando el anterior si existe.
     */
    public function registrarContrato(int $empleadoId, array $data): void
    {
        // 🔹 Validaciones antes de iniciar la transacción

        $validator = Validator::make($data, [
            'fecha_inicio' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (date('d', strtotime($value)) != 1) {
                        $fail('La fecha de inicio debe ser siempre el día 1.');
                    }
                }
            ],
            'tipo_planilla' => 'required',
            'plan_sp_codigo'=>'required',
            'tipo_contrato' => 'required',
            'modalidad_pago' => 'required'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $fechaInicio = Carbon::parse($data['fecha_inicio']);

        DB::beginTransaction();

        try {
            $empleado = PlanEmpleado::findOrFail($empleadoId);
            $ultimoContrato = $this->_obtenerUltimoContrato($empleadoId);

            $this->_validarFechaInicio($fechaInicio, $ultimoContrato, $empleado->nombres);

            if ($ultimoContrato) {
                $this->_finalizarContrato($ultimoContrato, $fechaInicio);
            }

            $data['plan_empleado_id'] = $empleadoId;
            
            PlanContrato::create($data);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Aplica cambios masivos de sueldo, creando un nuevo contrato para cada empleado.
     */
    public function guardarCambiosSueldos(array $cambios, string $mesVigencia, string $anioVigencia): void
    {
        if (empty($cambios)) {
            throw new Exception('No se proporcionaron cambios para procesar.');
        }
        if (!$mesVigencia || !$anioVigencia) {
            throw new Exception('Debe seleccionar el mes y el año de vigencia.');
        }

        $fechaInicio = Carbon::create($anioVigencia, $mesVigencia, 1)->startOfDay();

        DB::transaction(function () use ($cambios, $fechaInicio) {
            foreach ($cambios as $cambio) {
                $empleado = PlanEmpleado::findOrFail($cambio['empleado_id']);
                $nuevoSueldo = $cambio['nuevo_sueldo'];
                $ultimoContrato = $this->_obtenerUltimoContrato($empleado->id);

                // 1. Validar la fecha de inicio
                $this->_validarFechaInicio($fechaInicio, $ultimoContrato, $empleado->nombres);

                // 2. Finalizar el contrato anterior si existe
                if ($ultimoContrato) {
                    $this->_finalizarContrato($ultimoContrato, $fechaInicio);
                }

                // 3. Preparar y crear el nuevo contrato
                $nuevoContratoData = $this->_prepararDatosNuevoContrato($empleado, $ultimoContrato, $nuevoSueldo, $fechaInicio);
                PlanContrato::create($nuevoContratoData);
            }
        });
    }

    /**
     * Elimina un contrato y reajusta el historial del empleado.
     */
    public function eliminarContratoPorId(int $contratoId): void
    {
        DB::transaction(function () use ($contratoId) {
            $contratoAEliminar = PlanContrato::findOrFail($contratoId);
            $empleadoId = $contratoAEliminar->plan_empleado_id;

            // Buscamos el contrato anterior al que vamos a eliminar
            $contratoAnterior = PlanContrato::where('plan_empleado_id', $empleadoId)
                ->where('fecha_inicio', '<', $contratoAEliminar->fecha_inicio)
                ->orderByDesc('fecha_inicio')
                ->first();

            // Si existe un contrato anterior, le quitamos la fecha de fin para que vuelva a ser el activo
            if ($contratoAnterior) {
                $contratoAnterior->update(['fecha_fin' => null]);
            }

            $contratoAEliminar->delete();
        });
    }

    // ===================================================================
    // MÉTODOS PRIVADOS DE AYUDA (HELPERS)
    // ===================================================================

    /**
     * Obtiene el contrato más reciente de un empleado.
     */
    private function _obtenerUltimoContrato(int $empleadoId)
    {
        return PlanContrato::where('plan_empleado_id', $empleadoId)
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    /**
     * Valida que la fecha de inicio sea posterior a la del último contrato.
     */
    private function _validarFechaInicio(Carbon $fechaInicio, $ultimoContrato, string $nombreEmpleado): void
    {
        if ($ultimoContrato && $fechaInicio->lte(Carbon::parse($ultimoContrato->fecha_inicio))) {
            throw new Exception("La fecha de inicio debe ser posterior al último contrato para el empleado {$nombreEmpleado}");
        }
    }

    /**
     * Actualiza la fecha de fin de un contrato.
     */
    private function _finalizarContrato($contrato, Carbon $fechaInicioNuevoContrato): void
    {
        $contrato->update([
            'fecha_fin' => $fechaInicioNuevoContrato->copy()->subDay()->format('Y-m-d')
        ]);
    }

    /**
     * Prepara los datos para un nuevo contrato basándose en el anterior.
     */
    private function _prepararDatosNuevoContrato(Empleado $empleado, ?Contrato $ultimoContrato, float $nuevoSueldo, Carbon $fechaInicio): array
    {
        return [
            'empleado_id' => $empleado->id,
            'sueldo' => $nuevoSueldo,
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => null,
            'tipo_contrato' => $ultimoContrato?->tipo_contrato ?? 'indefinido',
            'cargo_codigo' => $ultimoContrato?->cargo_codigo ?? $empleado->cargo_id,
            'grupo_codigo' => $ultimoContrato?->grupo_codigo ?? $empleado->grupo_codigo,
            'tipo_planilla' => $ultimoContrato?->tipo_planilla ?? $empleado->tipo_planilla,
            'descuento_sp_id' => $ultimoContrato?->descuento_sp_id,
            'compensacion_vacacional' => $ultimoContrato?->compensacion_vacacional ?? 0,
            'esta_jubilado' => $ultimoContrato?->esta_jubilado ?? 0,
            'modalidad_pago' => $ultimoContrato?->modalidad_pago,
            'motivo_despido' => null,
        ];
    }


}