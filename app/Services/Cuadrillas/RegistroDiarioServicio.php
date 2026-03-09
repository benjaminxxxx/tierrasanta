<?php
// app/Services/Cuadrillas/RegistroDiarioServicio.php

namespace App\Services\Cuadrillas;

use App\Models\CuadRegistroDiario as RegistroDiario;


class RegistroDiarioServicio
{
    public function reemplazarCuadrillero(int $tramoLaboralId, int $anteriorId, int $nuevoId): void
    {
        RegistroDiario::where('tramo_laboral_id', $tramoLaboralId)
            ->where('cuadrillero_id', $anteriorId)
            ->update(['cuadrillero_id' => $nuevoId]);
    }
}