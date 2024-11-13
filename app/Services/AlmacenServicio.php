<?php

namespace App\Services;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use Carbon\Carbon;
use Exception;

class AlmacenServicio
{

    public function __construct()
    {

    }
    public static function eliminarRegistroSalida($registroId = null)
    {

        if (!$registroId) {
            throw new Exception('No se ha brindado el Identificador de Registro');
        }

        $registro = AlmacenProductoSalida::find($registroId);

        if (!$registro) {
            throw new Exception('No existe el Registro');
        }

        $compraProductoId = $registro->compra_producto_id;
        $registro->delete();
        if($compraProductoId){
            $compra = CompraProducto::find($compraProductoId);
            self::resetearFechaTermino($compra);
        }
        
    }
    public static function eliminarRegistrosPosteriores(CompraProducto $compra, $fechaDesde)
    {
        if (!$compra) {
            throw new Exception('No se ha brindado la compra');
        }
        if (!$fechaDesde) {
            throw new Exception('No se ha brindado la fecha');
        }

        $comprasPosteriores = CompraProducto::whereDate('fecha_compra', '>=', $compra->fecha_compra)->where('producto_id', $compra->producto_id)->get();
        foreach ($comprasPosteriores as $compraPosterior) {

            AlmacenProductoSalida::whereDate('fecha_reporte', '>=', $fechaDesde)->where('compra_producto_id', $compraPosterior->id)->update([
                'compra_producto_id' => null,
                'costo_por_kg' => null,
                'total_costo' => null,
                'item' => null
            ]);
            self::resetearFechaTermino($compraPosterior);
        }
    }
    public static function resetearFechaTermino(CompraProducto $compra)
    {

        if ($compra) {
            $cantidadUsada = AlmacenProductoSalida::where('compra_producto_id', $compra->id)->sum('cantidad');
            if ($cantidadUsada < $compra->stock) {
                $compra->fecha_termino = null;
                $compra->save();
            }
        }
    }
}
