<?php
namespace App\Services\Labor;

use App\Models\Labores;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LaborServicio
{
    /**
     * Leer registros con filtros y paginación opcional
     */
    public static function leer(array $filtros = [], ?int $porPagina = null, bool $verEliminados = false)
    {
        // Usamos withTrashed() de inicio si queremos flexibilidad, 
        // pero onlyTrashed() es más directo para lo que buscas.
        $query = Labores::query();

        // Lógica de visibilidad
        if ($verEliminados) {
            $query->onlyTrashed(); // Filtra para traer SOLO eliminados
        }
        // Si es false, Laravel por defecto ya aplica whereNull('deleted_at') 
        // gracias al trait SoftDeletes del modelo.

        $query->when($filtros['buscar'] ?? null, function ($q, $buscar) {
            // Agrupamos el OR para no romper los filtros de SoftDelete o Mano de Obra
            $q->where(function ($sub) use ($buscar) {
                $sub->where('nombre_labor', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        })
            ->when($filtros['mano_obra'] ?? null, function ($q, $manoObra) {
                $q->where('codigo_mano_obra', $manoObra);
            });

        $query->latest();

        return $porPagina ? $query->paginate($porPagina) : $query->get();
    }

    public static function guardar(array $data, ?int $id = null)
    {
        return $id ? self::actualizar($id, $data) : self::crear($data);
    }

    public static function crear(array $data)
    {
        $validados = self::validarYLimpiar($data);
        $validados['creado_por'] = auth()->id();

        return Labores::create($validados);
    }

    public static function actualizar(int $id, array $data)
    {
        $validados = self::validarYLimpiar($data, $id);
        $labor = Labores::findOrFail($id);
        $validados['actualizado_por'] = auth()->id();

        $labor->update($validados);
        return $labor;
    }

    public static function eliminar(int $id)
    {
        $labor = Labores::findOrFail($id);
        $labor->update(['eliminado_por' => auth()->id()]);
        return $labor->delete();
    }

    /**
     * Valida los datos y limpia el JSON de tramos
     */
    protected static function validarYLimpiar(array $data, ?int $id = null)
    {
        $validator = Validator::make($data, [
            'nombre_labor' => 'required|string|max:255',
            'codigo' => 'required|integer|unique:labores,codigo,' . $id,
            'codigo_mano_obra' => 'nullable|exists:mano_obras,codigo',
            'estandar_produccion' => 'nullable|integer|min:0',
            'unidades' => 'nullable|string|max:20',
            'tramos_bonificacion' => 'nullable', // Se limpia abajo
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'El :attribute ya existe.',
            'exists' => 'La :attribute no es válida.',
        ], [
            'nombre_labor' => 'nombre de labor',
            'codigo' => 'código',
            'codigo_mano_obra' => 'mano de obra',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $datosLimpios = $validator->validated();

        // Aplicamos tu lógica de limpieza de tramos
        $datosLimpios['tramos_bonificacion'] = self::filtrarTramosBonificacion($data['tramos_bonificacion'] ?? null);

        return $datosLimpios;
    }
    public static function eliminarExcepto(array $ids)
    {
        return Labores::whereNotIn('id', $ids)
            ->get()
            ->each(fn($labor) => self::eliminar($labor->id));
    }
    /**
     * Tu lógica de limpieza integrada
     */
    private static function filtrarTramosBonificacion($tramos): ?string
    {
        if (empty($tramos))
            return null;

        $decoded = is_string($tramos) ? json_decode($tramos, true) : $tramos;

        if (!is_array($decoded))
            return null;

        $filtered = array_filter($decoded, function ($item) {
            // Aseguramos que existan las llaves para evitar errores de índice
            return !empty($item['hasta']) || !empty($item['monto']);
        });

        return empty($filtered) ? null : json_encode(array_values($filtered));
    }
}