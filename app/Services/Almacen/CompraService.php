<?php

namespace App\Services\Almacen;

use App\Models\Compra;
use App\Services\Almacen\StockService;
use Illuminate\Support\Facades\DB;

class CompraService
{
    public function __construct(private StockService $stockService)
    {
    }

    public function crear(array $cabecera, array $detalles): Compra
    {
        return DB::transaction(function () use ($cabecera, $detalles) {
            $totales = $this->calcularTotales($detalles);

            $compra = Compra::create(array_merge($cabecera, $totales));

            $this->crearDetallesYStock($compra, $detalles);

            return $compra;
        });
    }

    public function actualizar(Compra $compra, array $cabecera, array $detalles): Compra
    {
        return DB::transaction(function () use ($compra, $cabecera, $detalles) {

            // 1. Revertir stock generado por la versión anterior de esta compra
            $this->stockService->revertirMovimientosDeOrigen(Compra::class, $compra->id);

            // 2. Borrar items/detalles anteriores
            $compra->detalles()->delete();

            // 3. Recalcular y actualizar cabecera
            $totales = $this->calcularTotales($detalles);
            $compra->update(array_merge($cabecera, $totales));

            // 4. Recrear items y stock con los nuevos valores
            $this->crearDetallesYStock($compra, $detalles);

            return $compra;
        });
    }

    private function crearDetallesYStock(Compra $compra, array $detalles): void
    {
        foreach ($detalles as $item) {
            $factorConversion = (float) ($item['factor_conversion_usado'] ?? $item['conversion_factor'] ?? 1);
            $cantidadBase = (float) $item['cantidad'] * $factorConversion;

            // Costo de inventario: precio neto (con descuento aplicado),
            // EXCLUYENDO IGV — el IGV es crédito fiscal recuperable, no forma
            // parte del costo del bien (criterio NIC 2).
            $montoBase = (float) $item['cantidad'] * (float) $item['costo_unitario'];
            $porcentajeDescuento = (float) ($item['porcentaje_descuento'] ?? $item['discount_percent'] ?? 0);
            $montoDescuento = $montoBase * ($porcentajeDescuento / 100);
            $costoNeto = $montoBase - $montoDescuento;
            $costoUnitarioBase = $cantidadBase > 0 ? round($costoNeto / $cantidadBase, 6) : 0;

            $compraDetalle = $compra->detalles()->create([
                'producto_id' => $item['producto_id'],
                'presentacion_id' => $item['presentacion_id'] ?? null,
                'nombre_presentacion' => $item['nombre_presentacion'] ?? null,
                'cantidad' => $item['cantidad'],
                'costo_unitario' => $item['costo_unitario'],
                'porcentaje_descuento' => $porcentajeDescuento,
                'porcentaje_igv' => $item['porcentaje_igv'] ?? $item['igv_percent'] ?? 18,
                'total_linea' => $this->calcularTotalLinea($item),
                'factor_conversion_usado' => $factorConversion,
                'cantidad_base' => $cantidadBase,
                'costo_unitario_base' => $costoUnitarioBase,
            ]);

            $this->stockService->registrarMovimiento(
                'entrada',
                $item['producto_id'],
                $compra->almacen_id,
                $cantidadBase,
                $compra->fecha_emision,
                $compra->tipo_kardex,
                Compra::class,
                $compra->id,
                ['compra_detalle_id' => $compraDetalle->id]
            );
        }
    }

    private function calcularTotalLinea(array $item): float
    {
        $base = (float) $item['cantidad'] * (float) $item['costo_unitario'];
        $porcentajeDescuento = (float) ($item['porcentaje_descuento'] ?? $item['discount_percent'] ?? 0);
        $porcentajeIgv = (float) ($item['porcentaje_igv'] ?? $item['igv_percent'] ?? 18);

        $descuento = $base * ($porcentajeDescuento / 100);
        $neto = $base - $descuento;
        $igv = $neto * ($porcentajeIgv / 100);

        return round($neto + $igv, 4);
    }

    private function calcularTotales(array $detalles): array
    {
        $subtotalNeto = 0;
        $igvTotal = 0;

        foreach ($detalles as $item) {
            $base = (float) $item['cantidad'] * (float) $item['costo_unitario'];
            $porcentajeDescuento = (float) ($item['porcentaje_descuento'] ?? $item['discount_percent'] ?? 0);
            $porcentajeIgv = (float) ($item['porcentaje_igv'] ?? $item['igv_percent'] ?? 18);

            $descuento = $base * ($porcentajeDescuento / 100);
            $neto = $base - $descuento;
            $igv = $neto * ($porcentajeIgv / 100);

            $subtotalNeto += $neto;
            $igvTotal += $igv;
        }

        return [
            'subtotal_neto' => round($subtotalNeto, 4),
            'igv_total' => round($igvTotal, 4),
            'total' => round($subtotalNeto + $igvTotal, 4),
        ];
    }
}