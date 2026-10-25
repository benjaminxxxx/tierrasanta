<?php

namespace App\Services\Planilla;

use App\Models\PlanConceptosConfig;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConceptoPlanillaServicio
{
    /**
     * Leer registros con filtros
     */
    public static function leer(array $filtros = [])
    {
        $query = PlanConceptosConfig::query();

        $query->when($filtros['buscar'] ?? null, function ($q, $buscar) {
            $q->where(function ($sub) use ($buscar) {
                $sub->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo_sunat', 'like', "%{$buscar}%")
                    ->orWhere('abreviatura_excel', 'like', "%{$buscar}%");
            });
        })
            ->when($filtros['clase'] ?? null, function ($q, $clase) {
                $q->where('clase', $clase);
            })
            ->when($filtros['origen'] ?? null, function ($q, $origen) {
                $q->where('origen', $origen);
            });

        return $query->orderBy('codigo_sunat', 'asc')->get();
    }

    public static function guardar(array $data, ?int $id = null)
    {
        return $id ? self::actualizar($id, $data) : self::crear($data);
    }

    public static function crear(array $data)
    {
        $validados = self::validar($data);
        return PlanConceptosConfig::create($validados);
    }

    public static function actualizar(int $id, array $data)
    {
        $validados = self::validar($data, $id);
        $concepto = PlanConceptosConfig::findOrFail($id);
        $concepto->update($validados);
        return $concepto;
    }

    public static function eliminar(int $id)
    {
        $concepto = PlanConceptosConfig::findOrFail($id);
        return $concepto->delete();
    }

    /**
     * Valida los datos según la estructura de la tabla
     */
    protected static function validar(array $data, ?int $id = null)
    {
        $validator = Validator::make($data, [
            'codigo_sunat' => 'nullable|string|max:4',
            'nombre' => 'required|string|max:255',
            'abreviatura_excel' => 'required|string|max:15',
            'clase' => 'required|in:ingreso,descuento,aporte_patronal',
            'origen' => 'required|in:blanco,negro',
            'metodo_calculo' => 'required|in:porcentaje,monto_fijo,manual',
            'valor_base' => 'required|numeric|min:0',
            'incluye_igv' => 'boolean',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'activo' => 'boolean',
        ]);

        $validator->after(function ($validator) use ($data, $id) {
            if ($validator->errors()->any())
                return;

            $codigo = $data['codigo_sunat'];
            $inicio = $data['fecha_inicio'];
            $fin = $data['fecha_fin'] ?? null;

            // 1. Validar que no haya más de un NULL para el mismo código
            if (is_null($fin)) {
                $existeNull = PlanConceptosConfig::where('codigo_sunat', $codigo)
                    ->whereNull('fecha_fin')
                    ->when($id, fn($q) => $q->where('id', '!=', $id))
                    ->exists();

                if ($existeNull) {
                    $validator->errors()->add('fecha_fin', "Ya existe un registro con vigencia indefinida (NULL) para el código {$codigo}. Solo uno puede estar abierto.");
                }
            }

            // 2. Validar Intersección de Fechas (Solapamiento)
            // La lógica es: (Inicio1 <= Fin2) AND (Fin1 >= Inicio2)
            $traslape = PlanConceptosConfig::where('codigo_sunat', $codigo)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->where(function ($query) use ($inicio, $fin) {
                    $query->where(function ($q) use ($inicio, $fin) {
                        // Caso donde el registro existente tiene fecha_fin
                        $q->whereNotNull('fecha_fin')
                            ->where('fecha_inicio', '<=', $fin ?? '9999-12-31')
                            ->where('fecha_fin', '>=', $inicio);
                    })->orWhere(function ($q) use ($inicio, $fin) {
                        // Caso donde el registro existente es indefinido (NULL)
                        $q->whereNull('fecha_fin')
                            ->where('fecha_inicio', '<=', $fin ?? '9999-12-31');
                    });
                })->exists();

            if ($traslape) {
                $validator->errors()->add('fecha_inicio', "Las fechas se intersectan con un registro existente del mismo código ({$codigo}).");
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Elimina registros que no estén en la lista enviada (para sincronización)
     */
    public static function eliminarExcepto(array $ids)
    {
        return PlanConceptosConfig::whereNotIn('id', $ids)->delete();
    }
}