<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadRegistroDiario;

class RegistroDiarioServicio
{
    /**
     * Asigna un costo personalizado a un registro diario específico.
     * * @param int $cuadrilleroId
     * @param string $fecha (Y-m-d)
     * @param mixed $costo
     */
    public function asignarCostoPersonalizado(int $cuadrilleroId, string $fecha, $costo): void
    {
        // Convertir string vacío, espacios o valores no numéricos a null
        $costoLimpio = (is_numeric($costo) && $costo >= 0) ? (float) $costo : null;

        CuadRegistroDiario::updateOrCreate(
            [
                'cuadrillero_id' => $cuadrilleroId,
                'fecha' => $fecha
            ],
            [
                'costo_personalizado_dia' => $costoLimpio
            ]
        );
    }
}