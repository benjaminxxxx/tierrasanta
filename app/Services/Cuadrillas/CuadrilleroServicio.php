<?php
// app/Services/Cuadrillas/CuadrilleroServicio.php

namespace App\Services\Cuadrillas;

use App\Models\Cuadrillero;

class CuadrilleroServicio
{
    public function encontrar(int $id): Cuadrillero
    {
        $cuadrillero = Cuadrillero::find($id);

        if (! $cuadrillero) {
            throw new \InvalidArgumentException("Cuadrillero #{$id} no encontrado.");
        }

        return $cuadrillero;
    }
}