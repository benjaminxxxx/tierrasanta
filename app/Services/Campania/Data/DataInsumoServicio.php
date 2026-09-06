<?php

namespace App\Services\Campania\Data;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\DistribucionCombustible;
class DataInsumoServicio
{
    public function generarCostoMaquinariaPor($campania, $campo, $fechaInicio, $fechaFin = null)
    {
        $fechaFin = $fechaFin ?? now();

        $distribuciones = DistribucionCombustible::with(['salidaCombustible.producto', 'maquinaria'])
            ->where('campo', $campo)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereHas('salidaCombustible.producto', function ($query) {
                $query->where('categoria_codigo', 'combustible');
            })
            ->get();

        return $distribuciones->map(function ($dist) use ($campania, $campo) {
            $nombreMaquinaria = $dist->maquinaria_nombre ?? ($dist->maquinaria ? $dist->maquinaria->nombre : 'N/A');

            return [
                'fecha' => $dist->fecha,
                'campania' => $campania,
                'campo' => $campo,
                'origen_id' => $dist->id,
                'tipo_gasto' => 'Maquinaria',
                'detalle_labor' => $dist->actividad ?? $nombreMaquinaria,
                'trabajador' => $nombreMaquinaria, // la "maquinaria" ocupa el rol de "quién ejecuta" en esta fila
                'horas' => $dist->horas !== null ? (float) $dist->horas : null,
                'cantidad_jornales' => null, // no aplica a maquinaria
                'cantidad' => $dist->cantidad_combustible !== null ? (float) $dist->cantidad_combustible : null,
                'proveedor' => null,
                'n_documento' => null,
                'costo' => (float) $dist->valor_costo,
                'observacion' => null,
            ];
        })->toArray();
    }
    /**
     * Obtiene la data base sin formato específico de keys.
     */
    private function obtenerDatosBase($campo, $categorias, $fechaInicio, $fechaFin)
    {
        $fechaFin = $fechaFin ?? now();

        $insumos = AlmacenProductoSalida::with(['producto'])
            ->where('campo_nombre', $campo)
            ->whereNull('maquinaria_id')
            ->whereHas('producto', function ($producto) use ($categorias) {
                $producto->whereIn('categoria_codigo', $categorias);
            })
            ->whereBetween('fecha_reporte', [$fechaInicio, $fechaFin])
            ->get();

        return $insumos->map(function ($insumo) {
            $ultimaCompra = CompraProducto::with(['proveedor'])
                ->where('producto_id', $insumo->producto_id)
                ->whereDate('fecha_compra', '<=', $insumo->fecha_reporte)
                ->orderBy('fecha_compra', 'desc')
                ->first();

            return [
                'fecha' => $insumo->fecha_reporte,
                'cantidad' => $insumo->cantidad,
                'nombre' => $insumo->producto->nombre_comercial,
                'orden' => $ultimaCompra?->orden_compra,
                'tienda' => $ultimaCompra?->proveedor?->nombre,
                'factura' => $ultimaCompra?->codigo_comprobante,
                'costo' => $insumo->total_costo,
            ];
        });
    }

    /**
     * Reemplaza generarCostoPesticidaPor() y generarCostoFertilizantePor().
     * Ya no depende de listas fijas de categoria_codigo: agrupa dinámicamente
     * por el grupo_operativo de la categoría del producto, así que una
     * categoría nueva (ej. "biológico" con grupo_operativo = "pesticida")
     * cae en su lugar automáticamente sin tocar código.
     */
    public function generarCostoInsumosPor(string $campania, string $campo, $fechaInicio, $fechaFin = null): array
    {
        $fechaFin = $fechaFin ?? now();

        $salidas = AlmacenProductoSalida::with(['producto.categoria'])
            ->where('campo_nombre', $campo)
            ->whereNull('maquinaria_id') // el combustible ligado a maquinaria se procesa aparte
            ->whereHas('producto.categoria', function ($q) {
                $q->whereIn('grupo_operativo', ['fertilizante', 'pesticida']);
            })
            ->whereBetween('fecha_reporte', [$fechaInicio, $fechaFin])
            ->get();

        return $salidas->map(function ($salida) use ($campania, $campo) {

            $ultimaCompra = CompraProducto::with(['proveedor'])
                ->where('producto_id', $salida->producto_id)
                ->whereDate('fecha_compra', '<=', $salida->fecha_reporte)
                ->orderBy('fecha_compra', 'desc')
                ->first();

            $grupoOperativo = $salida->producto->categoria->grupo_operativo ?? 'insumo';
            $tipoGasto = ucfirst($grupoOperativo); // 'fertilizante' -> 'Fertilizante', 'pesticida' -> 'Pesticida'

            return [
                'fecha' => $salida->fecha_reporte,
                'campania' => $campania,
                'campo' => $campo,
                'origen_id' => $salida->id,
                'tipo_gasto' => $tipoGasto,
                'grupo_operativo' => $grupoOperativo, // se usa para el origen_tipo al consolidar
                'detalle_labor' => $salida->producto->nombre_comercial ?? '-',
                'trabajador' => null,
                'horas' => null,
                'cantidad_jornales' => null,
                'cantidad' => $salida->cantidad !== null ? (float) $salida->cantidad : null,
                'proveedor' => $ultimaCompra?->proveedor?->nombre,
                'n_documento' => $this->armarDocumento($ultimaCompra?->orden_compra, $ultimaCompra?->codigo_comprobante),
                'costo' => (float) $salida->total_costo,
                'observacion' => null,
            ];
        })->toArray();
    }

    private function armarDocumento(?string $ordenCompra, ?string $factura): ?string
    {
        $partes = [];
        if ($ordenCompra)
            $partes[] = "OC-{$ordenCompra}";
        if ($factura)
            $partes[] = "F-{$factura}";

        return !empty($partes) ? implode(' / ', $partes) : null;
    }
}
