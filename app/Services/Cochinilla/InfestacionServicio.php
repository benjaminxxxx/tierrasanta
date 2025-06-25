<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use DB;
use Illuminate\Support\Carbon;

class InfestacionServicio
{
    public static function guardarInfestacion(array $datosInfestacion, array $ingresosRelacionados, ?int $infestacionId = null): int
    {
        return DB::transaction(function () use ($datosInfestacion, $ingresosRelacionados, $infestacionId) {
            if ($infestacionId) {
                $infestacion = CochinillaInfestacion::with('ingresos')->findOrFail($infestacionId);
                $infestacion->update($datosInfestacion);

                // ✅ Revertir stock de ingresos previamente asignados
                foreach ($infestacion->ingresos as $ingresoAnterior) {
                    $kgPrevios = $ingresoAnterior->pivot->kg_asignados;
                    $ingresoAnterior->stock_disponible += $kgPrevios;
                    $ingresoAnterior->save();
                }

                // ✅ Detach después de restaurar stock
                $infestacion->ingresos()->detach();
            } else {
                $infestacion = CochinillaInfestacion::create($datosInfestacion);
            }

            // ✅ Vincular ingresos nuevos y descontar stock
            foreach ($ingresosRelacionados as $ingresoId => $kg) {
                if ($kg > 0) {
                    $ingreso = CochinillaIngreso::findOrFail($ingresoId);

                    $stockActual = $ingreso->stock_disponible ?? $ingreso->total_kilos;
                    if ($kg > $stockActual) {
                        throw new \Exception("El ingreso {$ingreso->id} no tiene suficiente stock disponible.");
                    }

                    $ingreso->stock_disponible = $stockActual - $kg;
                    $ingreso->save();

                    $infestacion->ingresos()->attach($ingreso->id, [
                        'kg_asignados' => $kg,
                    ]);
                }
            }

            return $infestacion->id;
        });
    }

    public static function ultimasInfestaciones(array $filtro)
    {
        $query = CochinillaInfestacion::with([
            'campoCampania'
        ]);

        // Filtro por fecha de ingreso con tolerancia
        $fechaReferencia = isset($filtro['fecha']) ? Carbon::parse($filtro['fecha']) : now();
        $toleranciaDias = $filtro['tolerancia'] ?? 7;

        $query->whereDate('fecha', '<=', $fechaReferencia)
            ->whereDate('fecha', '>=', $fechaReferencia->copy()->subDays($toleranciaDias));

        return $query->orderBy('fecha', 'desc');
    }
}
