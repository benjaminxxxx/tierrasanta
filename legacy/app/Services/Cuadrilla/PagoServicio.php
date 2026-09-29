<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadRegistroDiario;

class PagoServicio
{
    public function obtenerPagosPorRango(string $fechaInicio, string $fechaFin, ?int $grupoId = null)
    {
        $query = CuadRegistroDiario::with('cuadrillero')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($grupoId) {
            $query->whereHas('cuadrillero.grupoActual', fn ($q) => $q->where('id', $grupoId));
        }

        return $query->get();
    }
}