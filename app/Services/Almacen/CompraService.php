<?php

namespace App\Services\Almacen;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Services\Almacen\StockService;
use Illuminate\Support\Facades\DB;

class CompraService
{
    public function __construct(private StockService $stockService)
    {
    }
    /*

    public function crear(array $cabecera, array $detalles): Compra
    {
        return DB::transaction(function () use ($cabecera, $detalles) {
            $totales = $this->calcularTotales($detalles);

            $compra = Compra::create(array_merge($cabecera, $totales));

            $this->crearDetallesYStock($compra, $detalles);

            return $compra;
        });
    }*/
    public function crear(array $cabecera, array $detalles): Compra
    {
        return DB::transaction(function () use ($cabecera, $detalles) {
            $compra = Compra::create(array_merge($cabecera, [
                'subtotal_neto' => 0,
                'igv_total' => 0,
                'total' => 0,
            ]));

            foreach ($detalles as $item) {
                $this->agregarDetalle($compra, $item);
            }

            $this->recalcularTotales($compra);

            return $compra->fresh();
        });
    }
    /*
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
    */
    public function actualizar(Compra $compra, array $cabecera, array $detalles): Compra
    {
        return DB::transaction(function () use ($compra, $cabecera, $detalles) {
            $this->stockService->revertirMovimientosDeOrigen(Compra::class, $compra->id);
            $compra->detalles()->delete();

            $compra->update($cabecera);

            foreach ($detalles as $item) {
                $this->agregarDetalle($compra, $item);
            }

            $this->recalcularTotales($compra);

            return $compra->fresh();
        });
    }
    private function esFactura(?string $codigoComprobante): bool
    {
        // Código SUNAT Tabla 10: '01' = Factura — el único comprobante común que
        // otorga derecho a crédito fiscal en compras. Si en tu operación otros
        // códigos (ej. Liquidación de Compra) también dan crédito fiscal, avísame
        // y amplío esta lista — por ahora solo '01' cuenta como tal.
        return $codigoComprobante === '01';
    }
    public function agregarDetalle(Compra $compra, array $item): CompraDetalle
    {
        $factorConversion = (float) ($item['factor_conversion_usado'] ?? $item['conversion_factor'] ?? 1);
        $cantidadBase = (float) $item['cantidad'] * $factorConversion;
        $porcentajeDescuento = (float) ($item['porcentaje_descuento'] ?? $item['discount_percent'] ?? 0);
        $porcentajeIgv = (float) ($item['porcentaje_igv'] ?? $item['igv_percent'] ?? 18);

        $montoBase = (float) $item['cantidad'] * (float) $item['costo_unitario'];
        $montoConDescuento = $montoBase - ($montoBase * ($porcentajeDescuento / 100));

        if ($this->esFactura($compra->tipo_comprobante_codigo)) {
            $subtotalNeto = $montoConDescuento;
            $totalLinea = $subtotalNeto * (1 + $porcentajeIgv / 100);
            $costoTotalKardex = $subtotalNeto; // crédito fiscal: el costo de inventario excluye IGV
        } else {
            $totalLinea = $montoConDescuento;
            $subtotalNeto = $totalLinea / (1 + $porcentajeIgv / 100);
            $costoTotalKardex = $totalLinea; // sin crédito fiscal: el IGV pagado sí es costo
        }

        $costoUnitarioBase = $cantidadBase > 0 ? round($costoTotalKardex / $cantidadBase, 6) : 0;
        $costoUnitarioNeto = (float) $item['cantidad'] > 0 ? round($subtotalNeto / $item['cantidad'], 6) : 0;

        $compraDetalle = $compra->detalles()->create([
            'producto_id' => $item['producto_id'],
            'presentacion_id' => $item['presentacion_id'] ?? null,
            'nombre_presentacion' => $item['nombre_presentacion'] ?? null,
            'cantidad' => $item['cantidad'],
            'costo_unitario' => $costoUnitarioNeto,
            'porcentaje_descuento' => $porcentajeDescuento,
            'porcentaje_igv' => $porcentajeIgv,
            'total_linea' => round($totalLinea, 4),
            'costo_total_kardex' => round($costoTotalKardex, 4), // monto real guardado, no recalculado
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
            CompraDetalle::class,
            $compraDetalle->id,
        );

        return $compraDetalle;
    }
    /**
     * Recalcula subtotal/IGV/total de la cabecera a partir de SUS detalles
     * actuales en BD (no de un array recibido). Se usa tanto después de
     * agregar líneas nuevas como después de borrar líneas de otros productos
     * en una cabecera compartida (ver eliminarComprasExistentes en el importador).
     */
    /*
    public function recalcularTotales(Compra $compra): void
    {
        $detalles = $compra->detalles()->get()->map(fn($d) => [
            'cantidad' => $d->cantidad,
            'costo_unitario' => $d->costo_unitario,
            'porcentaje_descuento' => $d->porcentaje_descuento,
            'porcentaje_igv' => $d->porcentaje_igv,
        ])->all();

        $compra->update($this->calcularTotales($detalles));
    }*/
    public function recalcularTotales(Compra $compra): void
    {
        $totalGeneral = (float) $compra->detalles()->sum('total_linea');
        $costoKardexGeneral = (float) $compra->detalles()->sum('costo_total_kardex');

        // Si es factura: costo_total_kardex = subtotal neto -> IGV = total - neto (positivo, como siempre).
        // Si NO es factura: costo_total_kardex = total_linea (son el mismo número) -> igv = 0 aquí,
        // pero el IGV sí existía "dentro" del monto, solo que no es crédito fiscal recuperable
        // así que subtotal_neto se calcula hacia atrás con el mismo criterio de agregarDetalle.
        if ($this->esFactura($compra->tipo_comprobante_codigo)) {
            $subtotalNeto = $costoKardexGeneral;
            $igvTotal = $totalGeneral - $subtotalNeto;
        } else {
            $porcentajeIgv = (float) ($compra->detalles()->value('porcentaje_igv') ?? 18);
            $subtotalNeto = $totalGeneral / (1 + $porcentajeIgv / 100);
            $igvTotal = $totalGeneral - $subtotalNeto;
        }

        $compra->update([
            'subtotal_neto' => round($subtotalNeto, 4),
            'igv_total' => round($igvTotal, 4),
            'total' => round($totalGeneral, 4),
        ]);
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
    /*
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
        }*/
}