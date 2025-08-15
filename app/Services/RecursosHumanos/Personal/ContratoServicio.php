<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\Contrato;
use App\Models\Empleado;
use Carbon\Carbon;
use DB;
use Exception;

class ContratoServicio
{
    /**
     * Registra un nuevo contrato para un empleado, finalizando el anterior si existe.
     */
    public function registrarContrato(int $empleadoId, array $data): void
    {
        $fechaInicio = Carbon::parse($data['fecha_inicio']);

        DB::beginTransaction();

        try {
            $empleado = Empleado::findOrFail($empleadoId);
            $ultimoContrato = $this->_obtenerUltimoContrato($empleadoId);

            // 1. Validar la fecha de inicio
            $this->_validarFechaInicio($fechaInicio, $ultimoContrato, $empleado->nombres);

            // 2. Finalizar el contrato anterior si existe
            if ($ultimoContrato) {
                $this->_finalizarContrato($ultimoContrato, $fechaInicio);
            }

            // 3. Crear el nuevo contrato
            $data['empleado_id'] = $empleadoId;
            Contrato::create($data);

            // 4. Actualizar los datos principales del empleado
            $this->_actualizarDatosEmpleado($empleado, [
                'salario' => $data['sueldo'],
                'cargo_id' => $data['cargo_codigo'],
                'grupo_codigo' => $data['grupo_codigo'],
                'descuento_sp_id' => $data['descuento_sp_id'],
                'compensacion_vacacional' => $data['compensacion_vacacional'] ?? 0,
                'esta_jubilado' => $data['esta_jubilado'] ?? 0,
                'tipo_planilla' => $data['tipo_planilla'],
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            // Registrar error
            \Log::error('Error registrando contrato', [
                'empleado_id' => $empleadoId,
                'error' => $e->getMessage(),
            ]);

            // Relanzar la excepción
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
                $empleado = Empleado::findOrFail($cambio['empleado_id']);
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
                Contrato::create($nuevoContratoData);

                // 4. Actualizar solo el salario y datos relacionados en el empleado
                $this->_actualizarDatosEmpleado($empleado, [
                    'salario' => $nuevoSueldo,
                    'cargo_id' => $nuevoContratoData['cargo_codigo'],
                    'grupo_codigo' => $nuevoContratoData['grupo_codigo'],
                    'descuento_sp_id' => $nuevoContratoData['descuento_sp_id'],
                    'compensacion_vacacional' => $nuevoContratoData['compensacion_vacacional'],
                    'esta_jubilado' => $nuevoContratoData['esta_jubilado'],
                    'tipo_planilla' => $nuevoContratoData['tipo_planilla'],
                ]);
            }
        });
    }

    /**
     * Elimina un contrato y reajusta el historial del empleado.
     */
    public function eliminarContratoPorId(int $contratoId): void
    {
        DB::transaction(function () use ($contratoId) {
            $contratoAEliminar = Contrato::findOrFail($contratoId);
            $empleadoId = $contratoAEliminar->empleado_id;

            // Buscamos el contrato anterior al que vamos a eliminar
            $contratoAnterior = Contrato::where('empleado_id', $empleadoId)
                ->where('fecha_inicio', '<', $contratoAEliminar->fecha_inicio)
                ->orderByDesc('fecha_inicio')
                ->first();

            // Si existe un contrato anterior, le quitamos la fecha de fin para que vuelva a ser el activo
            if ($contratoAnterior) {
                $contratoAnterior->update(['fecha_fin' => null]);

                // Actualizamos los datos del empleado para que reflejen los del contrato anterior
                $empleado = Empleado::findOrFail($empleadoId);
                $this->_actualizarDatosEmpleado($empleado, [
                    'salario' => $contratoAnterior->sueldo,
                    'cargo_id' => $contratoAnterior->cargo_codigo,
                    'grupo_codigo' => $contratoAnterior->grupo_codigo,
                    'descuento_sp_id' => $contratoAnterior->descuento_sp_id,
                    'compensacion_vacacional' => $contratoAnterior->compensacion_vacacional,
                    'esta_jubilado' => $contratoAnterior->esta_jubilado,
                    'tipo_planilla' => $contratoAnterior->tipo_planilla,
                ]);
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
    private function _obtenerUltimoContrato(int $empleadoId): ?Contrato
    {
        return Contrato::where('empleado_id', $empleadoId)
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    /**
     * Valida que la fecha de inicio sea posterior a la del último contrato.
     */
    private function _validarFechaInicio(Carbon $fechaInicio, ?Contrato $ultimoContrato, string $nombreEmpleado): void
    {
        if ($ultimoContrato && $fechaInicio->lte(Carbon::parse($ultimoContrato->fecha_inicio))) {
            throw new Exception("La fecha de inicio debe ser posterior al último contrato para el empleado {$nombreEmpleado}");
        }
    }

    /**
     * Actualiza la fecha de fin de un contrato.
     */
    private function _finalizarContrato(Contrato $contrato, Carbon $fechaInicioNuevoContrato): void
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

    /**
     * Actualiza los campos principales en el registro del empleado.
     */
    private function _actualizarDatosEmpleado(Empleado $empleado, array $datos): void
    {
        $empleado->update($datos);
    }
}