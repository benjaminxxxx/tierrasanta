<?php

namespace App\Services\Cochinilla;

use App\Models\CochinillaIngreso;
use Illuminate\Support\Carbon;

class IngresoServicio
{
    /**
     * Buscar ingresos de cochinilla disponibles para ser usados en una infestación.
     *
     * @param  string    $campo            Campo origen del ingreso.
     * @param  string    $fecha            Fecha base de referencia (habitualmente la de infestación).
     * @param  int       $tolerancia       Número de días hacia atrás desde la fecha para limitar el rango de búsqueda. Por defecto: 30.
     * @param  int|null  $infestacionId    (Opcional) ID de infestación actual, usado en edición para incluir ingresos ya asignados.
     * 
     *
     * Este método busca ingresos de cochinilla que:
     * - Pertenezcan al campo origen solicitado.
     * - Estén dentro de un rango de tiempo de `tolerancia` días hacia atrás desde la fecha.
     * - Cumplan alguna de estas condiciones:
     *     a) Tengan stock disponible (`stock_disponible > 0`).
     *     b) No hayan sido utilizados en ninguna infestación (`doesntHave`).
     *     c) Estén relacionados a la infestación actual en edición (`has` con infestacionId), incluso si su stock es 0.
     *
     * El filtro de 30 días sirve como optimización para no cargar miles de registros antiguos
     * que ya no son relevantes para operaciones recientes, y permite focalizar en ingresos del mes actual.
     */
    public static function buscarStock($campo, $fecha, $tolerancia = 30, $infestacionId = null)
    {
        $fechaMax = Carbon::parse($fecha);
        $fechaMin = $fechaMax->copy()->subDays($tolerancia);

        return CochinillaIngreso::where('campo', $campo)
            ->orderBy('fecha', 'desc')
            ->whereBetween('fecha', [$fechaMin, $fechaMax])
            ->where(function ($query) use ($infestacionId) {
                $query->where('stock_disponible', '>', 0)
                    ->orWhereDoesntHave('infestaciones')
                    ->orWhereHas('infestaciones', function ($sub) use ($infestacionId) {
                        if ($infestacionId) {
                            $sub->where('cochinilla_infestaciones.id', $infestacionId);
                        }
                    });
            });
    }
}
