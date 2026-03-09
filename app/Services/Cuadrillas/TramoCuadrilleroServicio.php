<?php
// app/Services/Cuadrillas/TramoCuadrilleroServicio.php

namespace App\Services\Cuadrillas;

use App\Models\CuadTramoLaboralCuadrillero as TramoCuadrillero;

class TramoCuadrilleroServicio
{
    public function existeEnTramo(int $tramoLaboralId, int $cuadrilleroId): bool
    {
        // cuad_tramo_cuadrilleros se relaciona al tramo via cuad_tramo_grupos
        return TramoCuadrillero::where('cuadrillero_id', $cuadrilleroId)
            ->whereHas('tramoLaboralGrupal', fn($q) => $q->where('cuad_tramo_laboral_id', $tramoLaboralId))
            ->exists();
    }

    public function reemplazarCuadrillero(int $tramoLaboralId, int $anteriorId, int $nuevoId): void
    {
        TramoCuadrillero::where('cuadrillero_id', $anteriorId)
            ->whereHas('tramoLaboralGrupal', fn($q) => $q->where('cuad_tramo_laboral_id', $tramoLaboralId))
            ->update(['cuadrillero_id' => $nuevoId]);
    }
}