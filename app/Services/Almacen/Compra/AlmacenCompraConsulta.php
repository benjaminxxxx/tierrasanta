<?php

namespace App\Services\Almacen\Compra;

use App\Models\Auditoria;
use App\Models\Compra;
use App\Models\CompraDetalle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lista de compras y su detalle por producto.
 *
 * Filtros: buscar (serie, número, proveedor o producto), producto (solo nombre de producto), tipoComprobante,
 * proveedorId, fechaDesde, fechaHasta. El producto se busca sin espacios: "ULTRAFERRO" también encuentra
 * "ULTRA FERRO", así se ven juntos los productos duplicados.
 */
class AlmacenCompraConsulta
{
    /** Nombre del producto sin espacios, para comparar "ULTRA FERRO" con "ULTRAFERRO". */
    private const NOMBRE_SIN_ESPACIOS = "REPLACE(UPPER(productos.nombre_comercial), ' ', '')";

    public static function textoSinEspacios(string $texto): string
    {
        return str_replace(' ', '', mb_strtoupper(trim($texto)));
    }

    /** Compras con sus líneas (y el producto de cada una), paginadas. */
    public function lista(array $filtros, bool $eliminadas, string $orden = 'fecha_emision', string $direccion = 'desc', int $porPagina = 10): LengthAwarePaginator
    {
        $orden = in_array($orden, ['fecha_emision', 'total'], true) ? $orden : 'fecha_emision';
        $direccion = $direccion === 'asc' ? 'asc' : 'desc';

        return Compra::query()
            ->with(['proveedor.persona', 'almacen', 'detalles.producto.tabla6'])
            ->when($eliminadas, fn($q) => $q->onlyTrashed())
            ->tap(fn($q) => $this->aplicarFiltros($q, $filtros))
            ->orderBy($orden, $direccion)
            ->orderByDesc('id')
            ->paginate($porPagina);
    }

    /**
     * Líneas de compra de los productos que coinciden con el filtro "producto": en qué compras se compró y cuánto.
     *
     * @return array{lineas: \Illuminate\Support\Collection, totales: \Illuminate\Support\Collection}
     *         totales por producto: producto, unidad, compras, cantidad (unidad base), total
     */
    public function lineasDeProducto(array $filtros, bool $eliminadas): array
    {
        $lineas = CompraDetalle::query()
            ->with(['producto.tabla6', 'compra' => fn($q) => $q->withTrashed()->with('proveedor.persona')])
            ->whereHas('compra', function ($q) use ($filtros, $eliminadas) {
                $eliminadas ? $q->onlyTrashed() : $q->whereNull('deleted_at');
                $this->aplicarFiltros($q, array_merge($filtros, ['producto' => null]));
            })
            ->whereHas('producto', fn($q) => $q->withTrashed()
                ->whereRaw(self::NOMBRE_SIN_ESPACIOS . ' LIKE ?', ['%' . self::textoSinEspacios($filtros['producto'] ?? '') . '%']))
            ->get()
            ->sortByDesc(fn($d) => $d->compra->fecha_emision?->format('Y-m-d') . str_pad((string) $d->compra_id, 10, '0', STR_PAD_LEFT))
            ->values();

        $totales = $lineas->groupBy('producto_id')->map(fn($grupo) => [
            'producto' => $grupo->first()->producto?->nombre_comercial ?? 'Producto eliminado',
            'producto_id' => $grupo->first()->producto_id,
            'unidad' => $grupo->first()->producto?->tabla6?->alias ?? '',
            'compras' => $grupo->pluck('compra_id')->unique()->count(),
            'cantidad' => (float) $grupo->sum('cantidad_base'),
            'total' => (float) $grupo->sum('total_linea'),
        ])->sortBy('producto')->values();

        return ['lineas' => $lineas, 'totales' => $totales];
    }

    /**
     * Quién eliminó cada compra, cuándo y por qué (último registro "eliminar" de la auditoría).
     *
     * @return array<int, array{usuario: ?string, fecha: ?\Carbon\Carbon, motivo: ?string}>
     */
    public function eliminaciones(array $compraIds): array
    {
        if (!$compraIds) {
            return [];
        }
        return Auditoria::where('modelo', Compra::class)
            ->whereIn('modelo_id', $compraIds)
            ->where('accion', 'eliminar')
            ->orderBy('fecha_accion')
            ->get()
            ->keyBy('modelo_id')
            ->map(fn($a) => ['usuario' => $a->usuario_nombre, 'fecha' => $a->fecha_accion, 'motivo' => $a->observacion])
            ->all();
    }

    /** Historial de auditoría de una compra (crear, eliminar, restaurar), del más reciente al más antiguo. */
    public function historial(int $compraId): array
    {
        return Auditoria::where('modelo', Compra::class)
            ->where('modelo_id', $compraId)
            ->orderByDesc('fecha_accion')
            ->orderByDesc('id')
            ->get()
            ->map(fn($a) => [
                'accion' => ($a->cambios['despues']['estado'] ?? null) === 'restaurada' ? 'restaurar' : $a->accion,
                'usuario' => $a->usuario_nombre,
                'fecha' => $a->fecha_accion,
                'motivo' => $a->observacion,
                'lineas' => $a->cambios['eliminado']['lineas'] ?? [],
            ])
            ->all();
    }

    private function aplicarFiltros(Builder $q, array $f): void
    {
        $buscar = trim((string) ($f['buscar'] ?? ''));
        $producto = trim((string) ($f['producto'] ?? ''));

        $q->when($buscar !== '', function ($q) use ($buscar) {
            $q->where(function ($sub) use ($buscar) {
                $sub->where('numero', 'like', "%{$buscar}%")
                    ->orWhere('serie', 'like', "%{$buscar}%")
                    ->orWhereRaw("CONCAT(COALESCE(serie, ''), '-', numero) LIKE ?", ["%{$buscar}%"])
                    ->orWhereHas('proveedor.persona', fn($p) => $p->where('razon_social', 'like', "%{$buscar}%")
                        ->orWhere('nombres', 'like', "%{$buscar}%"))
                    ->orWhereHas('detalles.producto', fn($p) => $p->withTrashed()
                        ->whereRaw(self::NOMBRE_SIN_ESPACIOS . ' LIKE ?', ['%' . self::textoSinEspacios($buscar) . '%']));
            });
        })
            ->when($producto !== '', fn($q) => $q->whereHas('detalles.producto', fn($p) => $p->withTrashed()
                ->whereRaw(self::NOMBRE_SIN_ESPACIOS . ' LIKE ?', ['%' . self::textoSinEspacios($producto) . '%'])))
            ->when($f['tipoComprobante'] ?? null, fn($q, $v) => $q->where('tipo_comprobante_codigo', $v))
            ->when($f['proveedorId'] ?? null, fn($q, $v) => $q->where('proveedor_id', $v))
            ->when($f['fechaDesde'] ?? null, fn($q, $v) => $q->whereDate('fecha_emision', '>=', $v))
            ->when($f['fechaHasta'] ?? null, fn($q, $v) => $q->whereDate('fecha_emision', '<=', $v));
    }
}
