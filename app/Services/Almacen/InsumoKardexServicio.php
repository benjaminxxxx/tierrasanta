<?php

namespace App\Services\Almacen;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\InsKardex;
use App\Models\Producto;
use Illuminate\Validation\Rule;
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
        $anio = $data['anio'] ?? null;
        $tipo = $data['tipo'] ?? null;

        return [
            'producto_id' => [
                'required',
                'exists:productos,id',
                Rule::unique('ins_kardexes')
                    ->where(
                        fn($query) => $query
                            ->where('anio', $anio)
                            ->where('tipo', $tipo)
                    )
                    ->ignore($kardexId)
            ],

            'codigo_existencia' => 'required|string|max:10',

            'anio' => 'required|integer|min:2000|max:2100',

            'tipo' => 'required|in:blanco,negro',

            'stock_inicial' => 'required|numeric|min:0',

            'costo_unitario' => 'required|numeric|min:0',

            'costo_total' => 'required|numeric|min:0',

            'metodo_valuacion' => 'nullable|in:promedio,peps',
            'tipo_compra_codigo_inicial' => [
                'nullable',
                'string',
                'exists:sunat_tabla10_tipo_comprobantes_pago,codigo'
            ],

            'serie_inicial' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9\-]+$/'
            ],

            'numero_inicial' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/'
            ],
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

        $messages = [
            'producto_id.required' => 'Debe seleccionar un producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'producto_id.unique' => 'Ya existe un kardex para este producto en el año y tipo seleccionados.',

            'codigo_existencia.required' => 'El código de existencia es obligatorio.',
            'codigo_existencia.string' => 'El código de existencia debe ser texto.',
            'codigo_existencia.max' => 'El código de existencia no puede superar los 10 caracteres.',

            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año debe ser mayor o igual a 2000.',
            'anio.max' => 'El año no puede ser mayor a 2100.',

            'tipo.required' => 'Debe indicar el tipo de kardex.',
            'tipo.in' => 'El tipo de kardex debe ser blanco o negro.',

            'stock_inicial.required' => 'El stock inicial es obligatorio.',
            'stock_inicial.numeric' => 'El stock inicial debe ser un número.',
            'stock_inicial.min' => 'El stock inicial no puede ser negativo.',

            'costo_unitario.required' => 'El costo unitario es obligatorio.',
            'costo_unitario.numeric' => 'El costo unitario debe ser un número.',
            'costo_unitario.min' => 'El costo unitario no puede ser negativo.',

            'costo_total.required' => 'El costo total es obligatorio.',
            'costo_total.numeric' => 'El costo total debe ser un número.',
            'costo_total.min' => 'El costo total no puede ser negativo.',

            'metodo_valuacion.in' => 'El método de valuación debe ser promedio o peps.',

            // tipo_compra_codigo_inicial
            'tipo_compra_codigo_inicial.string' => 'El tipo de comprobante debe ser texto.',
            'tipo_compra_codigo_inicial.exists' => 'El tipo de comprobante seleccionado no es válido.',
            'tipo_compra_codigo_inicial.required_with' => 'Debe completar el tipo de comprobante si ingresa serie o número.',

            // serie_inicial
            'serie_inicial.string' => 'La serie debe ser texto.',
            'serie_inicial.max' => 'La serie no puede tener más de 10 caracteres.',
            'serie_inicial.regex' => 'La serie solo puede contener letras, números y guiones.',
            'serie_inicial.required_with' => 'Debe ingresar la serie si completa el tipo o el número.',

            // numero_inicial
            'numero_inicial.string' => 'El número debe ser texto.',
            'numero_inicial.max' => 'El número no puede tener más de 20 caracteres.',
            'numero_inicial.regex' => 'El número solo debe contener dígitos.',
            'numero_inicial.required_with' => 'Debe ingresar el número si completa el tipo o la serie.',
        ];

        $validatedData = Validator::make($data, $rules, $messages)->validate();

        // 2. Establecer campos internos/por defecto si no están presentes
        // Estos campos no se validan, pero se aseguran de estar en el modelo
        $validatedData['tipo_compra_codigo_inicial'] =
            !empty(trim($validatedData['tipo_compra_codigo_inicial'] ?? ''))
            ? $validatedData['tipo_compra_codigo_inicial']
            : null;
        $validatedData['metodo_valuacion'] = $validatedData['metodo_valuacion'] ?? 'promedio';
        $validatedData['descripcion'] = Producto::find($validatedData['producto_id'])->nombre_comercial;
        $validatedData['codigo_existencia'] = mb_strtoupper($validatedData['codigo_existencia']);

        if ($kardexId) {

            $kardex = InsKardex::findOrFail($kardexId);
            $kardex->update($validatedData);

        } else {

            $kardex = InsKardex::create($validatedData);
        }

        // recalcular stock
        //remplazado por un trigger $this->sincronizarStockActual($kardex->id);

        return $kardex;
    }
    /*
    public function sincronizarStockActual(int $kardexId): void
    {
        $kardex = InsKardex::findOrFail($kardexId);

        $productoId = $kardex->producto_id;
        $anio = $kardex->anio;
        $tipo = $kardex->tipo;

        // Total compras del año
        $compras = CompraProducto::where('producto_id', $productoId)
            ->where('tipo_kardex', $tipo)
            ->whereYear('fecha_compra', $anio)
            ->sum('stock');

        // Total salidas del año
        $salidas = AlmacenProductoSalida::where('producto_id', $productoId)
            ->where('tipo_kardex', $tipo)
            ->whereYear('fecha_reporte', $anio)
            ->sum('cantidad');

        $stockActual = $kardex->stock_inicial + $compras - $salidas;

        $kardex->update([
            'stock_actual' => $stockActual
        ]);
    }
*/
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
    public function obtenerKardexes(
        array $filters = [],
        int $perPage = 15,
        ?string $sortField = null,
        ?string $sortDirection = 'asc'
    ) {
        $query = InsKardex::with('producto');

        // filtros...
        if (!empty($filters['filtroProducto'])) {
            $buscar = $filters['filtroProducto'];

            $query->whereHas('producto', function ($q) use ($buscar) {
                $q->whereRaw("
            CONCAT(
                TRIM(nombre_comercial),
                IF(ingrediente_activo IS NOT NULL AND ingrediente_activo != '',
                    CONCAT(' - ', TRIM(ingrediente_activo)),
                    ''
                )
            ) LIKE ?
        ", ["%{$buscar}%"]);
            });
        }

        if (!empty($filters['filtroAnio'])) {
            $query->where('anio', $filters['filtroAnio']);
        }

        if (!empty($filters['filtroTipo'])) {
            $query->where('tipo', $filters['filtroTipo']);
        }

        if (!empty($filters['filtroEstado'])) {
            $query->where('estado', $filters['filtroEstado']);
        }

        if (!empty($filters['filtroMetodo'])) {
            $query->where('metodo_valuacion', $filters['filtroMetodo']);
        }

        // ORDENAMIENTO
        if ($sortField) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            // default
            $query->orderBy('anio', 'desc')
                ->orderBy('producto_id')
                ->orderBy('tipo');
        }

        return $query->paginate($perPage);
    }
}