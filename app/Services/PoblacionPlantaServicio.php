<?php
namespace App\Services;

use App\Models\PoblacionPlantas;

class PoblacionPlantaServicio
{
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
