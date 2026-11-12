<?php

namespace App\Services\Cochinilla\Recuperacion;

use App\Exceptions\ErrorControlado;
use App\Models\CochinillaRecuperacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Cierre de la recuperación de un envío de mamá (ya no se esperan más infestadores de vuelta).
 * cochinilla_recuperaciones.campo es el campo que dio la mamá y tanda_inicio el primer día del envío.
 *
 * Ya no hay reparto de un lote entre varios campos: cada lote de infestadores es del campo con el que se registró
 * (CochinillaOrigenProceso).
 */
class CochinillaRecuperacionProceso
{
    /** Da por recuperado un envío de mamá. */
    public function cerrar(string $campo, string $tandaInicio, ?string $nota = null): CochinillaRecuperacion
    {
        $tanda = app(CochinillaRecuperacionConsulta::class)->tanda($campo, $tandaInicio);
        if (!$tanda) {
            throw new ErrorControlado("No se encontró el envío de mamá de {$campo} del " . Carbon::parse($tandaInicio)->format('d/m/Y') . '.');
        }
        return CochinillaRecuperacion::updateOrCreate(['campo' => $campo, 'tanda_inicio' => $tanda['inicio']], [
            'tanda_fin' => $tanda['fin'],
            'campo_campania_id' => $tanda['campania_id'],
            'kg_madres' => $tanda['kg_madres'],
            'kg_recuperados' => $tanda['kg_recuperados'],
            'nota' => $nota,
            'cerrada_por' => Auth::id(),
            'cerrada_en' => now(),
        ]);
    }

    public function reabrir(string $campo, string $tandaInicio): void
    {
        CochinillaRecuperacion::where('campo', $campo)->whereDate('tanda_inicio', $tandaInicio)->delete();
    }
}
