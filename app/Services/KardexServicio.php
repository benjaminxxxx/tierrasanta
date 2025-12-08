<?php
namespace App\Services;

use App\Models\InsKardex;
use App\Models\InsKardexReporte;
use App\Models\Kardex;
use App\Models\KardexConsolidado;
use DB;

class KardexServicio
{
    public static function procesarKardexConsolidado(InsKardexReporte $insumoKardexReporte)
    {
        $categorias = $insumoKardexReporte->categorias->pluck('categoria_codigo')->toArray();
        $kardexes = InsKardex::where('tipo', $insumoKardexReporte->tipo_kardex)
            ->with(['producto'])
            ->where('anio', $insumoKardexReporte->anio)
            ->whereHas('producto', function ($q) use ($categorias) {
                $q->whereIn('categoria_codigo', $categorias);
            })
            ->get();

        DB::beginTransaction();
        try {
            // Limpiamos los registros previos
            $insumoKardexReporte->detalles()->delete();

            foreach ($kardexes as $kardex) {
                $producto = $kardex->producto;

                if (!$producto) {
                    continue;
                }

                // 🔥 Obtener movimientos del año del reporte
                $movimientos = $kardex->movimientos;

                // ENTRADAS
                $entradasUnidades = $movimientos->sum('entrada_cantidad');
                $entradasImporte = $movimientos->sum('entrada_costo_total');

                // SALIDAS
                $salidasUnidades = $movimientos->sum('salida_cantidad');
                $salidasImporte = $movimientos->sum('salida_costo_total');
                // SALDO FINAL
                $saldoUnidades = $entradasUnidades - $salidasUnidades;
                $saldoImporte = $entradasImporte - $salidasImporte;

                // GUARDAR DETALLE
                $insumoKardexReporte->detalles()->create([
                    'reporte_id' => $insumoKardexReporte->id,
                    'ins_kardex_id' => $kardex->id,
                    'codigo_existencia' => $kardex->codigo_existencia,
                    'nombre_producto' => $producto->nombre_completo,
                    'condicion' => $kardex->condicion,
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
