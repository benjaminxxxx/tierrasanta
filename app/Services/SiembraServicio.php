<?php

namespace App\Services;

use App\Models\Siembra;
use Illuminate\Support\Facades\Auth;

class SiembraServicio
{
    /**
     * Query base de siembras aplicando filtros de campo/año.
     */
    public static function query(array $filtros = [])
    {
        $query = Siembra::query();

        if (!empty($filtros['campo_nombre'])) {
            $query->where('campo_nombre', $filtros['campo_nombre']);
        }

        if (!empty($filtros['anio'])) {
            $query->whereYear('fecha_siembra', $filtros['anio']);
        }

        return $query;
    }

    public static function listar(array $filtros, string $sortField = 'fecha_siembra', string $sortDirection = 'asc', int $perPage = 10)
    {
        return self::query($filtros)
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

    public static function aniosDisponibles(): array
    {
        return Siembra::selectRaw('YEAR(fecha_siembra) as anio')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio')
            ->toArray();
    }

    /**
     * Resumen anual: total de siembras y campos distintos por año.
     * Filtros independientes de los de la tabla principal.
     */
    public static function resumenAnual(array $filtros = [])
    {
        $query = Siembra::selectRaw('YEAR(fecha_siembra) as anio, COUNT(*) as total_siembras, COUNT(DISTINCT campo_nombre) as total_campos');

        if (!empty($filtros['campo_nombre'])) {
            $query->where('campo_nombre', $filtros['campo_nombre']);
        }

        if (!empty($filtros['anio'])) {
            $query->whereYear('fecha_siembra', $filtros['anio']);
        }

        return $query->groupBy('anio')
            ->orderByDesc('anio')
            ->get();
    }

    public static function crear(array $datos): Siembra
    {
        $datos['creado_por'] = Auth::user()->name;

        $siembra = Siembra::create($datos);

        AuditoriaServicio::registrar(
            modelo: Siembra::class,
            modeloId: $siembra->id,
            accion: 'crear',
            despues: $siembra->toArray(),
            camposIgnorados: ['updated_at', 'fecha_renovacion'],
        );

        return $siembra;
    }

    public static function actualizar(int $id, array $datos): Siembra
    {
        $siembra = Siembra::findOrFail($id);
        $siembra->update($datos);

        return $siembra;
    }

    public static function eliminar(int $id): void
    {
        $siembra = Siembra::findOrFail($id);

        $siembraData = $siembra->toArray();
        $siembraData['eliminado_por'] = Auth::user()->name;

        AuditoriaServicio::registrar(
            modelo: Siembra::class,
            modeloId: $siembra->id,
            accion: 'eliminar',
            antes: $siembraData,
            camposIgnorados: ['created_at', 'updated_at', 'fecha_renovacion'],
        );

        $siembra->delete();
    }
}