<?php

namespace App\Services\Planilla;

use App\Models\PlanSuspension;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PlanillaSuspensionProceso
{
    protected PlanillaSuspensionServicio $servicio;

    public function __construct(PlanillaSuspensionServicio $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * Guarda datos desde Handsontable (crear, actualizar, eliminar).
     *
     * @param array $datos Datos de Handsontable
     * @param int $mes Mes del período
     * @param int $anio Año del período
     * @return array Resultado de la operación
     * @throws Exception
     */
    public function guardarHandsontable(array $datos, int $mes, int $anio): array
    {
        DB::beginTransaction();

        try {
            $resultado = [
                'creados' => 0,
                'actualizados' => 0,
                'eliminados' => 0,
                'errores' => [],
            ];

            // 1. Obtener IDs de registros existentes en el mes
            $idsExistentesEnBD = $this->servicio->obtenerIdsDelMes($mes, $anio);

            // 2. Filtrar datos válidos
            $datosValidos = array_filter($datos, function ($fila) {
                return !empty($fila['plan_empleado_id']) &&
                    !empty($fila['tipo_suspension_id']) &&
                    !empty($fila['fecha_inicio']);
            });

            // 3. Extraer IDs enviados desde Handsontable
            $idsEnviadosDesdeHandsontable = array_filter(
                array_column($datosValidos, 'id'),
                fn($id) => !is_null($id) && $id > 0
            );

            // 4. IDs a eliminar = están en BD pero NO en Handsontable
            $idsAEliminar = array_diff($idsExistentesEnBD, $idsEnviadosDesdeHandsontable);

            // 5. Eliminar registros
            if (!empty($idsAEliminar)) {
                $eliminados = $this->servicio->eliminarMultiples($idsAEliminar);
                $resultado['eliminados'] = $eliminados;
            }

            // 6. Procesar crear/actualizar
            foreach ($datosValidos as $index => $fila) {
                // Validar que la fecha_inicio esté dentro del mes/año seleccionado
                $fechaInicio = Carbon::parse($fila['fecha_inicio']);

                if ($mes && $anio) {
                    if ($fechaInicio->month != $mes || $fechaInicio->year != $anio) {
                        throw new Exception(
                            "La fila #{$index} tiene fecha_inicio fuera del mes/año seleccionado. " .
                            "Esperado {$anio}-{$mes}, recibido: {$fechaInicio->format('Y-m-d')}"
                        );
                    }
                }
                if ($this->existeSolapamiento($fila)) {
                    throw new Exception("La suspensión se solapa con otra existente");
                }

                if (!empty($fila['id']) && $fila['id'] > 0) {
                    $this->servicio->actualizar($fila['id'], $fila);
                    $resultado['actualizados']++;
                } else {
                    $this->servicio->crear($fila);
                    $resultado['creados']++;
                }
            }

            DB::commit();
            return $resultado;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Verifica si existe solapamiento con validaciones adicionales.
     *
     * @param array $datos
     * @return bool
     */
    private function existeSolapamiento(array $datos): bool
    {
        $empleadoId = $datos['plan_empleado_id'];
        $fechaInicio = $datos['fecha_inicio'];
        $fechaFin = $datos['fecha_fin'] ?? null;
        $exceptoId = $datos['id'] ?? null;

        return $this->servicio->existeSolapamiento(
            $empleadoId,
            $fechaInicio,
            $fechaFin,
            $exceptoId
        );
    }

}