<?php

namespace App\Services\Almacen;

use App\Models\InsKardex;
use App\Models\Producto;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;

class InsumoKardexServicio
{/**
 * Define las reglas de validación base para un registro de Kárdex.
 * Incluye la lógica de unicidad condicional para el código de existencia.
 */
    protected function getBaseValidationRules(?int $kardexId = null, array $data): array
    {
        // NOTA: Para la regla unique, necesitamos que 'anio' y 'tipo' estén presentes en $data
        $anio = $data['anio'] ?? null;
        $tipo = $data['tipo'] ?? null;

        // Regla de unicidad CLAVE: codigo_existencia debe ser único
        // dentro de la combinación de anio y tipo.
        // Ignora el registro actual si se está editando ($kardexId es distinto de null).
        $uniqueRule = 'unique:ins_kardexes,codigo_existencia,' . ($kardexId ?? 'NULL') . ',id,anio,' . $anio . ',tipo,' . $tipo;

        return [
            'producto_id' => 'required|exists:productos,id',
            'codigo_existencia' => ['required', 'string', 'max:10', $uniqueRule],
            'anio' => 'required|integer|min:2000|max:2100',
            'tipo' => 'required|in:blanco,negro',
            'stock_inicial' => 'required|numeric|min:0',
            // Usamos una precisión alta para el costo unitario/total
            'costo_unitario' => 'required|numeric|min:0',
            'costo_total' => 'required|numeric|min:0',
            // Campos internos o adicionales que pueden ser enviados
            'metodo_valuacion' => 'nullable|in:promedio,peps',
        ];
    }

    /**
     * Crea o actualiza un registro de Kárdex.
     * Este es el método central solicitado.
     *
     * @param array $data Los datos del formulario del Kárdex.
     * @param int|null $kardexId El ID del Kárdex a actualizar (null para creación).
     * @return InsKardex
     * @throws ValidationException
     */
    public function guardarInsumoKardex(array $data, ?int $kardexId = null): InsKardex
    {
        // 1. Validar los datos de entrada
        $rules = $this->getBaseValidationRules($kardexId, $data);
        $validatedData = Validator::make($data, $rules)->validate();

        // 2. Establecer campos internos/por defecto si no están presentes
        // Estos campos no se validan, pero se aseguran de estar en el modelo
        $validatedData['metodo_valuacion'] = $validatedData['metodo_valuacion'] ?? 'promedio';
        $validatedData['descripcion'] = Producto::find($validatedData['producto_id'])->nombre_comercial;
        $validatedData['codigo_existencia'] = mb_strtoupper($validatedData['codigo_existencia']);

        if ($kardexId) {
            // **EDICIÓN**
            $kardex = InsKardex::findOrFail($kardexId);

            // **IMPORTANTE**: Aquí deberías implementar una regla de negocio
            // para prevenir la edición de campos clave (stock_inicial, anio, tipo, etc.)
            // si el Kárdex ya tiene movimientos registrados.

            $kardex->update($validatedData);
            return $kardex;

        } else {
            // **CREACIÓN**
            return InsKardex::create($validatedData);
        }
    }

    /**
     * Elimina un registro de Kárdex.
     *
     * @param int $kardexId
     * @return bool
     * @throws \Exception Si no se encuentra el Kárdex o si hay movimientos.
     */
    public function eliminarKardex(int $kardexId): bool
    {
        $kardex = InsKardex::findOrFail($kardexId);

        // Lógica de validación de negocio antes de eliminar:
        // if ($kardex->movimientos()->exists()) {
        //     throw new \Exception("No se puede eliminar el Kárdex porque ya tiene movimientos asociados.");
        // }

        return $kardex->delete();
    }

    /**
     * Obtiene una lista paginada de registros de Kárdex con filtros opcionales.
     *
     * @param array $filters Array asociativo de filtros (filtroAnio, filtroTipo, etc.).
     * @param int $perPage Número de elementos por página.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function obtenerKardexes(array $filters = [], int $perPage = 15)
    {
        $query = InsKardex::with('producto');

        // Aplicar Filtros

        // 1. Filtro por Año
        if (!empty($filters['filtroAnio'])) {
            $query->where('anio', $filters['filtroAnio']);
        }

        // 2. Filtro por Tipo
        if (!empty($filters['filtroTipo'])) {
            $query->where('tipo', $filters['filtroTipo']);
        }

        // 3. Filtro por Estado
        if (!empty($filters['filtroEstado'])) {
            $query->where('estado', $filters['filtroEstado']);
        }

        // 4. Filtro por Método de Valuación
        if (!empty($filters['filtroMetodo'])) {
            $query->where('metodo_valuacion', $filters['filtroMetodo']);
        }

        // Aplicar Ordenamiento y Paginación
        return $query->orderBy('anio', 'desc')
            ->orderBy('producto_id')
            ->orderBy('tipo')
            ->paginate($perPage);
    }
}