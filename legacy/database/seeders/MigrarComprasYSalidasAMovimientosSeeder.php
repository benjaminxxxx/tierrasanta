<?php

namespace Database\Seeders;

use App\Models\AlmacenProductoSalida;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\MovimientoStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrarComprasYSalidasAMovimientosSeeder extends Seeder
{
    public function run(): void
    {
        $this->migrarCompras();
        $this->migrarSalidas();
    }

    /**
     * Compra (moderna) + CompraDetalle -> MovimientoStock (entrada)
     * Origen = CompraDetalle, no Compra, porque cada línea puede ser
     * un producto distinto dentro de la misma compra.
     */
    private function migrarCompras(): void
    {
        CompraDetalle::with('compra')
            ->chunkById(500, function ($detalles) {
                foreach ($detalles as $detalle) {
                    $compra = $detalle->compra;

                    if (!$compra) {
                        $this->command?->warn("CompraDetalle #{$detalle->id} sin compra asociada, se omite.");
                        continue;
                    }

                    $yaExiste = MovimientoStock::where('origen_type', CompraDetalle::class)
                        ->where('origen_id', $detalle->id)
                        ->exists();

                    if ($yaExiste) {
                        continue;
                    }

                    if (is_null($compra->tipo_kardex)) {
                        $compra->update([
                            'tipo_kardex' => 'negro',
                        ]);
                    }

                    MovimientoStock::create([
                        'direccion' => 'entrada',
                        'producto_id' => $detalle->producto_id,
                        'almacen_id' => $compra->almacen_id,
                        'cantidad' => $detalle->cantidad_base,
                        'fecha_movimiento' => $compra->fecha_emision,
                        'tipo_kardex' => $compra->tipo_kardex ?? 'negro',
                        'origen_type' => CompraDetalle::class,
                        'origen_id' => $detalle->id,
                    ]);
                }
            });
    }

    /**
     * AlmacenProductoSalida -> MovimientoStock (salida)
     */
    private function migrarSalidas(): void
    {
        $almacenId = Almacen::first()?->id;

        if (!$almacenId) {
            throw new \RuntimeException('No hay almacén configurado; no se puede migrar salidas.');
        }

        AlmacenProductoSalida::chunkById(500, function ($salidas) use ($almacenId) {
            foreach ($salidas as $salida) {
                $yaExiste = MovimientoStock::where('origen_type', AlmacenProductoSalida::class)
                    ->where('origen_id', $salida->id)
                    ->exists();

                if ($yaExiste) {
                    continue;
                }

                if (is_null($salida->tipo_kardex)) {
                    $salida->update([
                        'tipo_kardex' => 'negro',
                    ]);
                }

                MovimientoStock::create([
                    'direccion' => 'salida',
                    'producto_id' => $salida->producto_id,
                    'almacen_id' => $almacenId,
                    'cantidad' => $salida->cantidad,
                    'fecha_movimiento' => $salida->fecha_reporte,
                    'tipo_kardex' => $salida->tipo_kardex ?? 'negro',
                    'origen_type' => AlmacenProductoSalida::class,
                    'origen_id' => $salida->id,
                ]);
            }
        });
    }
}