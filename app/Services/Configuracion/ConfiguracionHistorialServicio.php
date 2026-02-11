<?php

namespace App\Services\Configuracion;

use App\Models\ConfiguracionHistorial;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfiguracionHistorialServicio
{
    public static function valorVigente(string $codigo, int $mes, int $anio): float
    {
        $fechaConsulta = sprintf('%04d-%02d-01', $anio, $mes);

        $registro = ConfiguracionHistorial::where('configuracion_codigo', $codigo)
            ->where('fecha_inicio', '<=', $fechaConsulta)
            ->where(function ($q) use ($fechaConsulta) {
                $q->whereNull('fecha_fin')
                  ->orWhere('fecha_fin', '>=', $fechaConsulta);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$registro) {
            throw new \Exception(
                "No existe un valor vigente para '{$codigo}' en {$mes}/{$anio}."
            );
        }

        return (float) $registro->valor;
    }

    public static function leer(array $filtros = [])
    {
        // Opcional (filtros futuros)
        return ConfiguracionHistorial::orderBy('configuracion_codigo')
            ->orderBy('configuracion_codigo')
            ->orderBy('fecha_inicio')
            ->get();
    }

    public static function guardar(array $data, ?int $id = null)
    {
        return $id ? self::actualizar($id, $data) : self::crear($data);
    }

    public static function crear(array $data)
    {
        $validados = self::validar($data);
        return ConfiguracionHistorial::create($validados);
    }

    public static function actualizar(int $id, array $data)
    {
        $validados = self::validar($data, $id);

        $item = ConfiguracionHistorial::findOrFail($id);
        $item->update($validados);

        return $item;
    }

    public static function eliminarExcepto(array $ids)
    {
        return ConfiguracionHistorial::whereNotIn('id', $ids)->delete();
    }

    // ----------------------------------------------------------------------

    protected static function validar(array $data, ?int $id = null)
    {
        $validator = Validator::make($data, [
            'configuracion_codigo' => 'required|string|max:50',
            'valor' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $validator->after(function ($validator) use ($data, $id) {
            if ($validator->errors()->any()) {
                return;
            }

            $codigo = $data['configuracion_codigo'];
            $inicio = $data['fecha_inicio'];
            $fin = $data['fecha_fin'] ?? null;

            // ---------------------------
            // 1. Validar único registro con fecha_fin NULL
            // ---------------------------
            if (is_null($fin)) {
                $existeNull = ConfiguracionHistorial::where('configuracion_codigo', $codigo)
                    ->whereNull('fecha_fin')
                    ->when($id, fn($q) => $q->where('id', '!=', $id))
                    ->exists();

                if ($existeNull) {
                    $validator->errors()->add(
                        'fecha_fin',
                        "Ya existe una vigencia abierta para la configuración {$codigo}. Solo uno puede estar sin fecha_fin."
                    );
                }
            }

            // ---------------------------
            // 2. Validar intersección de fechas (NO solapamientos)
            // ---------------------------
            $traslape = ConfiguracionHistorial::where('configuracion_codigo', $codigo)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->where(function ($query) use ($inicio, $fin) {
                    $query->where(function ($q) use ($inicio, $fin) {
                        $q->whereNotNull('fecha_fin')
                            ->where('fecha_inicio', '<=', $fin ?? '9999-12-31')
                            ->where('fecha_fin', '>=', $inicio);
                    })->orWhere(function ($q) use ($inicio, $fin) {
                        $q->whereNull('fecha_fin')
                            ->where('fecha_inicio', '<=', $fin ?? '9999-12-31');
                    });
                })
                ->exists();

            if ($traslape) {
                $validator->errors()->add(
                    'fecha_inicio',
                    "Las fechas se solapan con un registro existente para {$codigo}."
                );
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}