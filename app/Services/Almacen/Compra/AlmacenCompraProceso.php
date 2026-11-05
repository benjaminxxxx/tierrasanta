<?php

namespace App\Services\Almacen\Compra;

use App\Models\Compra;
use App\Services\Almacen\CompraService;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Eliminación segura de compras: nunca se borra la fila (soft delete), se revierte el stock de cada línea y queda en
 * la auditoría quién la eliminó, cuándo, por qué y una copia de la compra con sus productos. Restaurar también pide
 * motivo. Solo bloquea un kardex CERRADO del periodo (StockService::verificarKardexAbierto).
 */
class AlmacenCompraProceso
{
    public const MOTIVO_MINIMO = 5;

    public function __construct(private CompraService $compras)
    {
    }

    public function eliminar(int $compraId, string $motivo): Compra
    {
        $motivo = $this->validarMotivo($motivo);
        $compra = Compra::with(['proveedor.persona', 'detalles.producto'])->findOrFail($compraId);

        DB::transaction(function () use ($compra, $motivo) {
            AuditoriaServicio::registrar(
                modelo: Compra::class,
                modeloId: $compra->id,
                accion: 'eliminar',
                antes: $this->copia($compra),
                observacion: $motivo,
            );
            $compra->update(['eliminado_por' => auth()->id()]);
            $this->compras->eliminar($compra);
        });

        return $compra;
    }

    public function restaurar(int $compraId, string $motivo): Compra
    {
        $motivo = $this->validarMotivo($motivo);
        $compra = Compra::onlyTrashed()->findOrFail($compraId);

        DB::transaction(function () use ($compra, $motivo) {
            $this->compras->restaurar($compra);
            $compra->update(['eliminado_por' => null]);
            // auditorias.accion solo admite crear/editar/eliminar: la restauración es un "editar" del estado
            AuditoriaServicio::registrar(
                modelo: Compra::class,
                modeloId: $compra->id,
                accion: 'editar',
                antes: ['estado' => 'eliminada'],
                despues: ['estado' => 'restaurada'],
                observacion: $motivo,
            );
        });

        return $compra;
    }

    private function validarMotivo(string $motivo): string
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < self::MOTIVO_MINIMO) {
            throw ValidationException::withMessages(['motivo' => 'Escribe el motivo (mínimo ' . self::MOTIVO_MINIMO . ' letras): queda en la auditoría.']);
        }
        return $motivo;
    }

    /** Copia legible de la compra para la auditoría: cabecera + cada producto con su cantidad y monto. */
    private function copia(Compra $compra): array
    {
        $persona = $compra->proveedor?->persona;
        return [
            'comprobante' => trim(($compra->serie ? $compra->serie . '-' : '') . $compra->numero),
            'tipo_comprobante' => $compra->tipo_comprobante_codigo,
            'fecha_emision' => $compra->fecha_emision?->format('Y-m-d'),
            'proveedor' => $persona?->razon_social ?: $persona?->nombres,
            'tipo_kardex' => $compra->tipo_kardex,
            'moneda' => $compra->moneda,
            'total' => (float) $compra->total,
            'forma_pago' => $compra->forma_pago,
            'notas' => $compra->notas,
            'lineas' => $compra->detalles->map(fn($d) => [
                'detalle_id' => $d->id,
                'producto_id' => $d->producto_id,
                'producto' => $d->producto?->nombre_comercial,
                'presentacion' => $d->nombre_presentacion,
                'cantidad' => (float) $d->cantidad,
                'cantidad_base' => (float) $d->cantidad_base,
                'costo_unitario' => (float) $d->costo_unitario,
                'total_linea' => (float) $d->total_linea,
            ])->values()->all(),
        ];
    }
}
