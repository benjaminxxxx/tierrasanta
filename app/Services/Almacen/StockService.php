<?php
// app/Services/StockService.php
namespace App\Services\Almacen;

use App\Models\InsKardexMovimiento;
use App\Models\StockProducto;
use App\Models\MovimientoStock;
use App\Models\Almacen;
use App\Models\TransferenciaAlmacen;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Registra un movimiento real (entrada o salida) Y actualiza
     * el saldo materializado en el mismo paso, atómicamente.
     */
    public function registrarMovimiento(
        string $direccion,
        int $productoId,
        int $almacenId,
        float $cantidad,
        string $fechaMovimiento,
        string $tipoKardex,
        ?string $origenType = null,
        ?int $origenId = null,
        array $extra = []
    ): MovimientoStock {
        return DB::transaction(function () use ($direccion, $productoId, $almacenId, $cantidad, $fechaMovimiento, $tipoKardex, $origenType, $origenId, $extra) {

            // Validación preventiva en salidas manuales
            if ($direccion === 'salida') {
                $disponible = self::disponible($productoId, $almacenId);
                if ($cantidad > $disponible) {
                    throw new \RuntimeException("Stock insuficiente. Disponible: {$disponible}, solicitado: {$cantidad}.");
                }
            }

            $movimiento = MovimientoStock::create(array_merge([
                'direccion' => $direccion,
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
                'cantidad' => $cantidad,
                'fecha_movimiento' => $fechaMovimiento,
                'tipo_kardex' => $tipoKardex,
                'origen_type' => $origenType,
                'origen_id' => $origenId,
            ], $extra));

            $stock = StockProducto::firstOrCreate(
                ['producto_id' => $productoId, 'almacen_id' => $almacenId, 'tipo_kardex' => $tipoKardex],
                ['cantidad' => 0]
            );

            $delta = $direccion === 'entrada' ? $cantidad : -$cantidad;
            $stock->increment('cantidad', $delta);

            return $movimiento;
        });
    }

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
        return DB::transaction(function () use ($productoId, $almacenOrigenId, $almacenDestinoId, $cantidad, $fechaTransferencia,$tipoKardex) {

            $disponible = self::disponible($productoId, $almacenOrigenId);
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

    /** Saldo cacheado — rápido, sin sumar historial completo */
    public static function disponible(int $productoId, int $almacenId, string $tipoKardex): float
    {
        return (float) (StockProducto::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
        ->where('tipo_kardex', $tipoKardex)
            ->value('cantidad') ?? 0);
    }

    /**
     * Saldo REAL, recalculado desde movimientos_stock.
     * Se usa solo para arqueo/auditoría.
     */
    public static function recalculadoDesdeMovimientos(int $productoId, int $almacenId, string $tipoKardex): float
{
    $entradas = (float) MovimientoStock::where('producto_id', $productoId)
        ->where('almacen_id', $almacenId)
        ->where('tipo_kardex', $tipoKardex)
        ->where('direccion', 'entrada')->sum('cantidad');

    $salidas = (float) MovimientoStock::where('producto_id', $productoId)
        ->where('almacen_id', $almacenId)
        ->where('tipo_kardex', $tipoKardex)
        ->where('direccion', 'salida')->sum('cantidad');

    return $entradas - $salidas;
}
public static function obtenerStockPorTipo(int $productoId, int $almacenId): array
{
    $filas = StockProducto::where('producto_id', $productoId)
        ->where('almacen_id', $almacenId)
        ->pluck('cantidad', 'tipo_kardex');

    return [
        'blanco' => (float) ($filas['blanco'] ?? 0),
        'negro' => (float) ($filas['negro'] ?? 0),
    ];
}

    /**
     * Arqueo: compara el saldo cacheado contra el recalculado.
     */
    public static function auditar(int $productoId, int $almacenId): array
    {
        $cacheado = self::disponible($productoId, $almacenId);
        $real = self::recalculadoDesdeMovimientos($productoId, $almacenId);

        return [
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'cacheado' => $cacheado,
            'calculado' => $real,
            'coincide' => abs($cacheado - $real) < 0.0001, // tolerancia por decimales flotantes
            'diferencia' => $cacheado - $real,
        ];
    }

    public function revertirMovimientosDeOrigen(
        string $origenType,
        int $origenId,
        ?int $almacenId = null,
        ?int $productoId = null
    ): void {
        DB::transaction(function () use ($origenType, $origenId, $almacenId, $productoId) {
            $movimientos = MovimientoStock::where('origen_type', $origenType)
                ->where('origen_id', $origenId)
                ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
                ->when($productoId, fn($q) => $q->where('producto_id', $productoId))
                ->get();

            foreach ($movimientos as $mov) {
                // Validar usando el modelo InsKardexMovimiento y el nuevo campo stock_movimiento_id
                $tieneKardex = InsKardexMovimiento::where('stock_movimiento_id', $mov->id)
                    ->where('estado', 'activo') // Asegura verificar que el registro no esté anulado
                    ->exists();

                if ($tieneKardex) {
                    throw new \RuntimeException(
                        "El movimiento de stock #{$mov->id} ya está procesado en el Kárdex para este periodo. " .
                        "No se puede modificar la distribución. Debe eliminar o anular primero el Kárdex generado y volver a intentar."
                    );
                }

                $stock = StockProducto::firstOrCreate(
                    ['producto_id' => $mov->producto_id, 'almacen_id' => $mov->almacen_id],
                    ['cantidad' => 0]
                );

                $delta = $mov->direccion === 'entrada' ? -$mov->cantidad : $mov->cantidad;
                $stock->increment('cantidad', $delta);

                $mov->delete();
            }
        });
    }
}