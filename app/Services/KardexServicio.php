<?php
namespace App\Services;

use App\Models\Kardex;
use App\Models\KardexConsolidado;
use DB;

class KardexServicio
{
    public static function listarKardexConsolidado($kardexId, $esBlanco = true)
    {
        $filtro = $esBlanco ? 'blanco' : 'negro';

        return KardexConsolidado::where('kardex_id', $kardexId)
            ->where('tipo_kardex', $filtro)
            ->orderBy('categoria_producto', 'asc')
            ->orderByRaw("CAST(SUBSTRING(codigo_existencia, 2) AS UNSIGNED) ASC") // Extrae número después de la letra y ordena numérico
            ->get();

    }
    public static function procesarKardexConsolidado(int $kardexId, bool $esBlanco = false)
    {
        $kardex = Kardex::find($kardexId);

        if (!$kardex) {
            throw new \Exception("Kardex no encontrado.");
        }

        $filtro = $esBlanco ? 'blanco' : 'negro';

        $productos = $kardex->productos()
            ->where('tipo_kardex', $filtro)
            ->get();

        DB::beginTransaction();
        try {
            // Limpiamos los registros previos
            KardexConsolidado::where('kardex_id', $kardexId)
                ->where('tipo_kardex', $filtro)
                ->delete();

            foreach ($productos as $productoKardex) {
                $producto = $productoKardex->producto;

                // Filtrar entradas/salidas por tipo_kardex explícitamente

                $entradas = $kardex->compras($producto->id)->where('tipo_kardex', $filtro)->get();
                $salidas = $kardex->salidas($producto->id)->where('tipo_kardex', $filtro)->get();

                $entradasUnidades = $entradas->sum('stock') + $productoKardex->stock_inicial;
                $entradasImporte = $entradas->sum('total') + $productoKardex->costo_total;
                $salidasUnidades = $salidas->sum('cantidad');
                $salidasImporte = $salidas->sum('total_costo');

                $saldoUnidades = $entradasUnidades - $salidasUnidades;
                $saldoImporte = $entradasImporte - $salidasImporte;

                KardexConsolidado::create([
                    'kardex_id' => $kardexId,
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre_completo,
                    'codigo_existencia' => $productoKardex->codigo_existencia,
                    'categoria_producto' => $producto->categoria ?? null,
                    'tipo_kardex' => $filtro,
                    'condicion' => $productoKardex->condicion ?? null,
                    'unidad_medida' => $producto->tabla6?->codigo . ' - ' . $producto->tabla6?->alias,
                    'total_entradas_unidades' => $entradasUnidades,
                    'total_entradas_importe' => $entradasImporte,
                    'total_salidas_unidades' => $salidasUnidades,
                    'total_salidas_importe' => $salidasImporte,
                    'saldo_unidades' => $saldoUnidades,
                    'saldo_importe' => $saldoImporte,
                ]);
            }


            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e; // Re-lanzamos para que el caller lo maneje
        }
    }
}
