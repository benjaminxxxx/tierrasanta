<?php

// Método retirado de App\Services\Almacen\StockService (ver legacy/README.md). Nadie lo llamaba.

namespace App\Services\Almacen;

class StockServiceTransferencia
{
    /**
     * Traslado entre almacenes: saca del almacén de origen y
     * aumenta en el almacén de destino (2 movimientos: salida + entrada).
     */
    public function transferir(
        int $productoId,
        int $almacenOrigenId,
        int $almacenDestinoId,
        float $cantidad,
        string $fechaTransferencia,
        string $tipoKardex
    ): TransferenciaAlmacen {
        return DB::transaction(function () use ($productoId, $almacenOrigenId, $almacenDestinoId, $cantidad, $fechaTransferencia, $tipoKardex) {

            $disponible = self::disponible($productoId, $almacenOrigenId, $tipoKardex);
            if ($cantidad > $disponible) {
                throw new \RuntimeException(
                    "Stock insuficiente en el almacén de origen. Disponible: {$disponible}, solicitado: {$cantidad}."
                );
            }

            $transferencia = TransferenciaAlmacen::create([
                'producto_id' => $productoId,
                'almacen_origen_id' => $almacenOrigenId,
                'almacen_destino_id' => $almacenDestinoId,
                'cantidad' => $cantidad,
                'fecha_transferencia' => $fechaTransferencia,
            ]);

            $this->registrarMovimiento('salida', $productoId, $almacenOrigenId, $cantidad, $fechaTransferencia, $tipoKardex, TransferenciaAlmacen::class, $transferencia->id);
            $this->registrarMovimiento('entrada', $productoId, $almacenDestinoId, $cantidad, $fechaTransferencia, $tipoKardex, TransferenciaAlmacen::class, $transferencia->id);

            return $transferencia;
        });
    }
}
