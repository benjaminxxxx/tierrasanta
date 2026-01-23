<?php

namespace App\Services\Campania\Data;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\DistribucionCombustible;
class DataInsumoServicio
{
    public function generarCostoMaquinariaPor($campo, $fechaInicio, $fechaFin = null)
    {
        $fechaFin = $fechaFin ?? now();

        $distribuciones = DistribucionCombustible::with(['salidaCombustible.producto', 'maquinaria'])
            ->where('campo', $campo) // El campo está en la distribución
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereHas('salidaCombustible.producto', function ($query) {
                $query->where('categoria_codigo', 'combustible');
            })
            ->get();

        // Mapeamos los datos usando los Accessors dinámicos
        $data = $distribuciones->map(function ($dist) {
            return [
                'fecha' => $dist->fecha,
                'maquinaria' => $dist->maquinaria_nombre ?? ($dist->maquinaria ? $dist->maquinaria->nombre : 'N/A'),
                'actividad' => $dist->actividad,
                'horas' => $dist->horas, // Accessor dinámico
                'cantidad_combustible' => $dist->cantidad_combustible, // Accessor dinámico
                'maquinaria_costo' => $dist->valor_costo, // Accessor dinámico
            ];
        })
        ->toArray();
        return $data;
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
            $ultimaCompra = CompraProducto::with(['tiendaComercial'])
                ->where('producto_id', $insumo->producto_id)
                ->whereDate('fecha_compra', '<=', $insumo->fecha_reporte)
                ->orderBy('fecha_compra', 'desc')
                ->first();

            return [
                'fecha' => $insumo->fecha_reporte,
                'cantidad' => $insumo->cantidad,
                'nombre' => $insumo->producto->nombre_comercial,
                'orden' => $ultimaCompra?->orden_compra,
                'tienda' => $ultimaCompra?->tiendaComercial?->nombre,
                'factura' => $ultimaCompra?->factura,
                'costo' => $insumo->total_costo,
            ];
        });
    }

    /**
     * Mapeador genérico para cambiar las llaves.
     */
    private function formatearData($coleccion, $prefijo)
    {
        return $coleccion->map(function ($item) use ($prefijo) {
            return [
                'fecha' => $item['fecha'],
                "consumo_{$prefijo}_cantidad" => $item['cantidad'],
                "consumo_{$prefijo}_nombre_comercial" => $item['nombre'],
                "consumo_{$prefijo}_orden_compra" => $item['orden'],
                "consumo_{$prefijo}_tienda_comercial" => $item['tienda'],
                "consumo_{$prefijo}_factura" => $item['factura'],
                "consumo_{$prefijo}_costo" => $item['costo'],
            ];
        })->toArray();
    }

    public function generarCostoPesticidaPor($campo, $fechaInicio, $fechaFin = null)
    {
        $data = $this->obtenerDatosBase($campo, ['pesticida'], $fechaInicio, $fechaFin);
        return $this->formatearData($data, 'pesticida');
    }

    public function generarCostoFertilizantePor($campo, $fechaInicio, $fechaFin = null)
    {
        $data = $this->obtenerDatosBase($campo, ['corrector_salinidad', 'fertilizante'], $fechaInicio, $fechaFin);
        return $this->formatearData($data, 'fertilizante');
    }
}
