<?php

namespace App\Services\Costos\Data;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraDetalle;
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
                'minutos' => $dist->horas !== null ? (float) $dist->horas*60 : null,
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
            $ultimaCompra = $this->ultimaCompra($insumo->producto_id, $insumo->fecha_reporte);

            return [
                'fecha' => $insumo->fecha_reporte,
                'cantidad' => $insumo->cantidad,
                'nombre' => $insumo->producto->nombre_comercial,
                'orden' => null,
                'tienda' => $ultimaCompra['proveedor'],
                'factura' => $ultimaCompra['documento'],
                'costo' => $insumo->total_costo,
            ];
        });
    }

    /**
     * Proveedor y comprobante de la última compra del producto hasta la fecha dada
     * (tablas compras/compra_detalles; compra_productos quedó en legacy/).
     *
     * @return array{proveedor: ?string, documento: ?string}
     */
    private function ultimaCompra(int $productoId, $fecha): array
    {
        $detalle = CompraDetalle::with('compra.proveedor.persona')
            ->where('producto_id', $productoId)
            ->whereHas('compra', fn($q) => $q->whereDate('fecha_emision', '<=', $fecha))
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->orderByDesc('compras.fecha_emision')
            ->orderByDesc('compra_detalles.id')
            ->select('compra_detalles.*')
            ->first();

        $compra = $detalle?->compra;
        $documento = $compra && ($compra->serie || $compra->numero)
            ? trim("{$compra->serie}-{$compra->numero}", '-')
            : null;

        return [
            'proveedor' => $compra?->proveedor?->razon_social ?: null,
            'documento' => $documento,
        ];
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
        return $this->salidasInsumos($fechaInicio, $fechaFin ?? now(), $campo)
            ->map(fn($salida) => $this->mapearSalidaInsumo($salida, $campania))
            ->toArray();
    }

    /**
     * Salidas de productos cuyo grupo operativo es fertilizante o pesticida
     * (la categoría/subcategoría no importa aquí, solo el grupo operativo).
     */
    public function salidasInsumos($fechaInicio, $fechaFin, ?string $campo = null)
    {
        return AlmacenProductoSalida::with(['producto.categoria'])
            ->when($campo, fn($q) => $q->where('campo_nombre', $campo))
            ->whereNull('maquinaria_id') // el combustible ligado a maquinaria se procesa aparte
            ->whereHas('producto.categoria', function ($q) {
                $q->whereIn('grupo_operativo', ['fertilizante', 'pesticida']);
            })
            ->whereBetween('fecha_reporte', [$fechaInicio, $fechaFin])
            ->get();
    }

    public function mapearSalidaInsumo(AlmacenProductoSalida $salida, string $campania): array
    {
        $ultimaCompra = $this->ultimaCompra($salida->producto_id, $salida->fecha_reporte);

        $grupoOperativo = $salida->producto->categoria->grupo_operativo ?? 'insumo';
        $tipoGasto = ucfirst($grupoOperativo); // 'fertilizante' -> 'Fertilizante', 'pesticida' -> 'Pesticida'

        return [
            'fecha' => $salida->fecha_reporte,
            'campania' => $campania,
            'campo' => $salida->campo_nombre,
            'origen_id' => $salida->id,
            'tipo_gasto' => $tipoGasto,
            'grupo_operativo' => $grupoOperativo, // se usa para el origen_tipo al consolidar
            'detalle_labor' => $salida->producto->nombre_comercial ?? '-',
            'trabajador' => null,
            'horas' => null,
            'cantidad_jornales' => null,
            'cantidad' => $salida->cantidad !== null ? (float) $salida->cantidad : null,
            'proveedor' => $ultimaCompra['proveedor'],
            'n_documento' => $ultimaCompra['documento'], // serie-número del comprobante
            'costo' => (float) $salida->total_costo,
            'observacion' => null,
        ];
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
