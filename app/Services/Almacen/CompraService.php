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
    /**
     * Edita una compra conservando la historia de movimientos:
     * - línea con id existente: se actualiza; su movimiento de stock se ajusta EN SU LUGAR solo si
     *   cambia algo que lo afecta (producto, cantidad, fecha, tipo, almacén) o su costo;
     * - línea sin id: es nueva y genera su propio movimiento;
     * - línea que ya no viene: se elimina junto con su movimiento.
     * Cambios de cabecera que no afectan movimientos (proveedor, serie, notas...) no los tocan.
     * Solo se bloquea si el kardex del periodo afectado está cerrado.
     *
     * (Antes se revertía con origen Compra::class, pero los movimientos son de CompraDetalle:
     * no se revertía nada y cada edición duplicaba el stock.)
     */
    public function actualizar(Compra $compra, array $cabecera, array $detalles): Compra
    {
        return DB::transaction(function () use ($compra, $cabecera, $detalles) {
            $compra->update($cabecera);
            $compra->refresh();

            $existentes = $compra->detalles()->get()->keyBy('id');
            $idsRecibidos = [];

            foreach ($detalles as $item) {
                $id = $item['id'] ?? null;

                if ($id && $existentes->has($id)) {
                    $this->actualizarDetalle($compra, $existentes[$id], $item);
                    $idsRecibidos[] = (int) $id;
                } else {
                    $this->agregarDetalle($compra, $item);
                }
            }

            foreach ($existentes->except($idsRecibidos) as $quitado) {
                $this->stockService->revertirMovimientosDeOrigen(CompraDetalle::class, $quitado->id);
                $quitado->delete();
            }

            $this->recalcularTotales($compra);

            return $compra->fresh();
        });
    }

    /**
     * Elimina (soft delete) una compra revirtiendo el movimiento de cada línea. Los movimientos
     * son de CompraDetalle, no de Compra: revertir por Compra::class no revertía nada y el stock
     * y el kardex seguían contando la compra eliminada. Bloquea si el kardex está cerrado.
     */
    public function eliminar(Compra $compra): void
    {
        DB::transaction(function () use ($compra) {
            foreach ($compra->detalles as $detalle) {
                $this->stockService->revertirMovimientosDeOrigen(CompraDetalle::class, $detalle->id);
            }
            $compra->delete();
        });
    }

    /**
     * Restaura una compra eliminada y vuelve a registrar el movimiento de cada línea.
     */
    public function restaurar(Compra $compra): void
    {
        DB::transaction(function () use ($compra) {
            $compra->restore();

            foreach ($compra->detalles as $detalle) {
                $this->stockService->actualizarMovimientoDeOrigen(CompraDetalle::class, $detalle->id, [
                    'direccion' => 'entrada',
                    'producto_id' => $detalle->producto_id,
                    'almacen_id' => $compra->almacen_id,
                    'cantidad' => (float) $detalle->cantidad_base,
                    'fecha_movimiento' => $compra->fecha_emision,
                    'tipo_kardex' => $compra->tipo_kardex,
                    'costo_unitario' => (float) $detalle->costo_unitario_base,
                    'costo_total' => (float) $detalle->costo_total_kardex,
                ]);
            }
        });
    }

    /**
     * Actualiza una línea existente y ajusta su movimiento solo si algo que lo afecta cambió
     * (incluye cambios de cabecera como fecha, tipo de kardex o almacén).
     */
    private function actualizarDetalle(Compra $compra, CompraDetalle $detalle, array $item): void
    {
        $linea = $this->calcularLinea($item);
        $detalle->update($linea);

        $this->stockService->actualizarMovimientoDeOrigen(CompraDetalle::class, $detalle->id, [
            'direccion' => 'entrada',
            'producto_id' => $linea['producto_id'],
            'almacen_id' => $compra->almacen_id,
            'cantidad' => $linea['cantidad_base'],
            'fecha_movimiento' => $compra->fecha_emision,
            'tipo_kardex' => $compra->tipo_kardex,
            'costo_unitario' => $linea['costo_unitario_base'],
            'costo_total' => $linea['costo_total_kardex'],
        ]);
    }

    /**
     * Atributos de una línea de compra a partir de lo ingresado (precio y total con IGV).
     */
    private function calcularLinea(array $item): array
    {
        // El formulario envía 'factor_conversion' (antes se ignoraba y toda presentación contaba como 1)
        $factorConversion = (float) ($item['factor_conversion_usado'] ?? $item['factor_conversion'] ?? $item['conversion_factor'] ?? 1) ?: 1;
        $cantidadBase = (float) $item['cantidad'] * $factorConversion;
        $porcentajeDescuento = (float) ($item['porcentaje_descuento'] ?? $item['discount_percent'] ?? 0);
        $porcentajeIgv = (float) ($item['porcentaje_igv'] ?? $item['igv_percent'] ?? 18);

        $montoBase = (float) $item['cantidad'] * (float) $item['costo_unitario'];
        $montoConDescuento = $montoBase - ($montoBase * ($porcentajeDescuento / 100));

        $totalLinea = isset($item['total_linea']) && (float) $item['total_linea'] > 0
            ? (float) $item['total_linea']
            : $montoConDescuento;
        $costoTotalKardex = $totalLinea; // sin crédito fiscal: el IGV pagado es costo

        return [
            'producto_id' => $item['producto_id'],
            'presentacion_id' => $item['presentacion_id'] ?? null,
            'nombre_presentacion' => $item['nombre_presentacion'] ?? null,
            'cantidad' => $item['cantidad'],
            'costo_unitario' => round((float) $item['costo_unitario'], 6), // con IGV, tal como se ingresó
            'porcentaje_descuento' => $porcentajeDescuento,
            'porcentaje_igv' => $porcentajeIgv,
            'total_linea' => round($totalLinea, 4),
            'costo_total_kardex' => round($costoTotalKardex, 4), // monto real guardado, no recalculado
            'factor_conversion_usado' => $factorConversion,
            'cantidad_base' => $cantidadBase,
            'costo_unitario_base' => $cantidadBase > 0 ? round($costoTotalKardex / $cantidadBase, 6) : 0,
        ];
    }
    /**
     * Regla de costo: el precio unitario y el total de línea SIEMPRE incluyen IGV, sea factura,
     * boleta u otro comprobante. La empresa vende cochinilla exonerada de IGV y no tiene crédito
     * fiscal, así que el IGV pagado es costo real del inventario. El IGV solo se desglosa como dato.
     *
     * $item: cantidad, costo_unitario (con IGV), porcentaje_descuento, porcentaje_igv y,
     * opcionalmente, total_linea (con IGV): si viene, manda sobre cantidad × precio para no
     * perder centavos cuando el usuario ingresa el subtotal de la línea.
     */
    public function agregarDetalle(Compra $compra, array $item): CompraDetalle
    {
        $linea = $this->calcularLinea($item);
        $compraDetalle = $compra->detalles()->create($linea);

        // El movimiento guarda también el costo: es lo que el kardex valoriza
        $this->stockService->registrarMovimiento(
            'entrada',
            $item['producto_id'],
            $compra->almacen_id,
            $linea['cantidad_base'],
            $compra->fecha_emision,
            $compra->tipo_kardex,
            CompraDetalle::class,
            $compraDetalle->id,
            [
                'costo_unitario' => $linea['costo_unitario_base'],
                'costo_total' => $linea['costo_total_kardex'],
            ],
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
        $detalles = $compra->detalles()->get(['total_linea', 'porcentaje_igv']);

        // total_linea ya incluye IGV en todos los comprobantes; la base imponible se
        // calcula hacia atrás por línea (cada una puede tener su propio % de IGV).
        $totalGeneral = (float) $detalles->sum('total_linea');
        $subtotalNeto = (float) $detalles->sum(
            fn($d) => (float) $d->total_linea / (1 + (float) ($d->porcentaje_igv ?? 18) / 100)
        );
        $igvTotal = $totalGeneral - $subtotalNeto;

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