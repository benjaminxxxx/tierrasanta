<?php

namespace App\Services\Cochinilla;

use App\Models\VentaCochinilla;

class VentaServicio
{
    public static function guardar(array $data, ?int $ventaId = null)
    {
        if ($ventaId) {
            $venta = VentaCochinilla::findOrFail($ventaId);
            $venta->update($data);
            return $venta;
        }

        return VentaCochinilla::create($data);
    }
}