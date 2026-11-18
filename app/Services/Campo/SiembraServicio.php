<?php

namespace App\Services\Campo;

use App\Models\Siembra;
use Illuminate\Support\Facades\Auth;
use App\Services\Reporte\AuditoriaServicio;

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
            ->with('campania:id,nombre_campania,campo')
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
        // A mano en Campo → Siembras: es una plantación del campo
        $datos += ['tipo' => 'siembra', 'origen' => 'registro'];

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
        // Es la misma siembra que la del mapa de etapas: se quita desde ahí (si no, el mapa quedaría sin su registro)
        $enMapa = $siembra->sectores()->with('campania:id,nombre_campania,campo')->first();
        if ($enMapa) {
            throw new \App\Exceptions\ErrorControlado("Esta siembra es la «{$enMapa->nombre}» de la campaña {$enMapa->campania?->nombre_campania} (campo {$enMapa->campania?->campo}). "
                . 'Quítala o cambia su fecha en Campañas → Siembras y etapas de esa campaña.');
        }

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