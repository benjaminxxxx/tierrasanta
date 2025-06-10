<?php
namespace App\Services;

use App\Models\PoblacionPlantas;
use App\Models\PoblacionPlantasDetalle;
use DB;
use Illuminate\Validation\ValidationException;

class PoblacionPlantaServicio
{
    public static function registrar(array $datosGenerales, array $detalles): PoblacionPlantas
    {
        // Validación manual avanzada antes de guardar
        if (empty($datosGenerales['area_lote']) || $datosGenerales['area_lote'] <= 0) {
            throw ValidationException::withMessages(['area_lote' => 'El área del lote es obligatoria y debe ser mayor a 0.']);
        }

        if (empty($datosGenerales['metros_cama']) || $datosGenerales['metros_cama'] <= 0) {
            throw ValidationException::withMessages(['metros_cama' => 'Los metros de cama son obligatorios y deben ser mayores a 0.']);
        }

        if (empty($datosGenerales['empleado_id']) || !is_numeric($datosGenerales['empleado_id'])) {
            throw ValidationException::withMessages(['empleado_id' => 'Debe seleccionar un evaluador válido.']);
        }

        if (empty($datosGenerales['fecha'])) {
            throw ValidationException::withMessages(['fecha' => 'La fecha es obligatoria.']);
        }

        if (empty($datosGenerales['tipo_evaluacion'])) {
            throw ValidationException::withMessages(['tipo_evaluacion' => 'El tipo de evaluación es obligatorio.']);
        }

        // Validar detalles
        $filtrados = collect($detalles)->filter(function ($fila) {
            return !empty($fila['cama_muestreada']) && !empty($fila['longitud_cama']) && !empty($fila['plantas_x_cama']);
        });

        if ($filtrados->count() !== count($detalles)) {
            throw ValidationException::withMessages(['detalle' => 'Hay filas del detalle con campos obligatorios vacíos.']);
        }

        return DB::transaction(function () use ($datosGenerales, $filtrados) {
            // Crear o actualizar registro principal
            $poblacion = isset($datosGenerales['id'])
                ? PoblacionPlantas::findOrFail($datosGenerales['id'])->update($datosGenerales)
                : PoblacionPlantas::create($datosGenerales);

            $id = $datosGenerales['id'] ?? $poblacion->id;

            // Borrar anteriores detalles si es actualización
            PoblacionPlantasDetalle::where('poblacion_plantas_id', $id)->delete();

            // Insertar nuevos detalles con cálculo de plantas_x_metro
            $detallesInsert = $filtrados->map(function ($fila) use ($id) {
                $longitud = floatval($fila['longitud_cama']);
                $plantas = intval($fila['plantas_x_cama']);

                if ($longitud <= 0) {
                    throw ValidationException::withMessages(['detalle' => 'La longitud de cama debe ser mayor que 0.']);
                }

                return [
                    'poblacion_plantas_id' => $id,
                    'cama_muestreada' => intval($fila['cama_muestreada']),
                    'longitud_cama' => $longitud,
                    'plantas_x_cama' => $plantas,
                    'plantas_x_metro' => round($plantas / $longitud, 3),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            PoblacionPlantasDetalle::insert($detallesInsert->toArray());

            return PoblacionPlantas::find($id);
        });
    }
    public static function listarConFiltros(array $filtros)
    {
        $query = PoblacionPlantas::query()
            ->with(['campania'])
            ->orderBy('fecha', 'asc');

        if (!empty($filtros['campo'])) {
            $query->whereHas('campania', function ($q) use ($filtros) {
                $q->where('campo', $filtros['campo']);
            });
        }

        if (!empty($filtros['campania_id'])) {
            $query->where('campania_id', $filtros['campania_id']);
        }

        return $query->paginate(20);
    }
    public static function eliminar(int $id): int
    {
        $poblacion = PoblacionPlantas::findOrFail($id);
        $campaniaId = $poblacion->campania_id;
        $poblacion->delete();
        return $campaniaId;
    }
}
