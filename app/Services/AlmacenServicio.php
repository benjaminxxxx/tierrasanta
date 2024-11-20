<?php

namespace App\Services;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
use App\Models\Kardex;
use App\Models\KardexProducto;
use Carbon\Carbon;
use Exception;

class AlmacenServicio
{

    public function __construct()
    {

    }
    /*
    public static function registrarStockKardex(string $tipoKardex, AlmacenProductoSalida $almacenRegistro, float $cantidad)
    {
        $fecha_reporte = Carbon::parse($almacenRegistro->fecha_reporte);
        $kardex = Kardex::whereYear('fecha_inicial', $fecha_reporte)
            ->where('tipo_kardex', $tipoKardex)
            ->where('eliminado', false)
            ->first();

        if (!$kardex) {
            return [
                'response' => false,
                'message' => 'Falta el kardex ' . $tipoKardex . ' para el año ' . $fecha_reporte->year
            ];
        }

        $kardexProducto = $kardex->productos()->where('producto_id', $almacenRegistro->producto_id)->first();
        if (!$kardexProducto) {
            return [
                'response' => false,
                'message' => 'Falta el kardex ' . $tipoKardex . ' para el producto ' . $almacenRegistro->producto->nombre_completo
            ];
        }

        if ($kardexProducto) {
            $stockPorUsar = $kardexProducto->stock_inicial;
            if ($stockPorUsar > 0) {

                $fechaInicial = $kardex->fecha_inicial;
                $fechaFinal = $kardex->fecha_final;
                //si hay stock inicial, deberia haber una compra con fecha anterior a la fecha inicial y con estado fecha_termino null
                $existeCompraAnteriorAbierta = CompraProducto::whereDate('fecha_compra', '<', $fecha_reporte)
                    ->where('fecha_compra', '<', $kardex->fecha_inicial)
                    ->whereNull('fecha_termino')
                    ->where('tipo_kardex', $kardex->tipo_kardex)
                    ->orderBy('fecha_compra', 'asc') //menor fecha a mayor fecha: mas antigua primero, primero en comprarse primera en salir
                    ->first();

                if (!$existeCompraAnteriorAbierta) {
                    return [
                        'response' => false,
                        'message' => 'El kardex "' . $kardex->nombre . '" de tipo "' . $kardex->tipo_kardex . '" del producto ' . $kardexProducto->producto->nombre_completo . ' tiene un stock inicial de ' . $stockPorUsar . $kardexProducto->producto->unidad_medida . ', para lo cual debe registrar su compra con fecha anterior a ' . $kardex->fecha_inicial
                    ];
                }

                $StockInicialUtilizado = AlmacenProductoSalida::where('producto_id', $almacenRegistro->producto_id)
                    ->whereBetween('fecha_reporte', [$fechaInicial, $fechaFinal])
                    ->whereHas('compra', function ($query) use ($fechaInicial) {
                        $query->where('fecha_compra', '<', $fechaInicial);
                    });

                $existeStockInicialUtilizado = $StockInicialUtilizado->exists();
                if (!$existeStockInicialUtilizado) {
                    return [
                        'response' => false,
                        'message' => 'El kardex "' . $kardex->nombre . '" de tipo "' . $kardex->tipo_kardex . '" del producto ' . $kardexProducto->producto->nombre_completo . ' tiene un stock inicial de ' . $stockPorUsar . $kardexProducto->producto->unidad_medida . ', para lo cual debe registrar su compra con fecha anterior a ' . $kardex->fecha_inicial
                    ];
                }
                $sumaStockInicialUtilizado = $StockInicialUtilizado->sum('cantidad');
                $stockDisponible = $stockPorUsar - $sumaStockInicialUtilizado;

                if ($stockDisponible < $cantidad) {
                    //pueda que no haya cantidad suficiente, compensar con alguna compra nueva de esta temporada

                    return [
                        'response' => false,
                        'message' => 'Cantidad '
                    ];
                } else {
                    $almacenRegistro->cantidad = $cantidad;
                    $almacenRegistro->costo_por_kg = $cantidad;

                }
            }
        }

        return [
            'response' => true,
            'message' => 'Stock registrado'
        ];
    }*/
    /*
    public static function registrarStock(AlmacenProductoSalida $almacenRegistro, float $cantidad)
    {
        $fecha_reporte = Carbon::parse($almacenRegistro->fecha_reporte);

        $intentarKardexBlanco = self::registrarStockKardex('blanco', $almacenRegistro, $cantidad);
        $errores = [];

        if (!$intentarKardexBlanco['response']) {
            $errores[] = $intentarKardexBlanco['message'];

            $intentarKardexNegro = self::registrarStockKardex('negro', $almacenRegistro, $cantidad);

            if (!$intentarKardexNegro['response']) {
                $errores[] = $intentarKardexNegro['message'];
                throw new Exception("Se debe corregir algunos de estos errores:<br/> " . implode('<br/>', $errores));
            }
        }

        dd(5);

        //como requisito inicial, debe existir un kardex para el año donde este el registro, revisar primero eso
        $kardexBlanco = Kardex::whereYear('fecha_inicial', $fecha_reporte->year)
            ->where('tipo_kardex', 'blanco')
            ->where('eliminado', false)
            ->first();
        $kardexNegro = Kardex::whereYear('fecha_inicial', $fecha_reporte->year)
            ->where('tipo_kardex', 'negro')
            ->where('eliminado', false)
            ->first();


        if (!$kardexBlanco || !$kardexNegro) {

        }
        $kardexProductoBlanco = $kardexBlanco->productos()->where('producto_id', $almacenRegistro->producto_id)->first();
        $kardexProductoNegro = $kardexNegro->productos()->where('producto_id', $almacenRegistro->producto_id)->first();

        if (!$kardexProductoBlanco || !$kardexProductoNegro) {

            throw new Exception("Debe crear un Kardex blanco y negro para el producto: " . $almacenRegistro->producto->nombre_completo . " para saber a donde registrar el stock.");

        }
        if ($kardexProductoBlanco) {
            $stockPorUsar = $kardexProductoBlanco->stock_inicial;
            if ($stockPorUsar > 0) {
                //verificar que el stock inicial haya sido utilizado
                $fechaInicial = $kardexBlanco->fecha_inicial;
                $fechaFinal = $kardexBlanco->fecha_final;
                $sumaStockInicialUtilizado = AlmacenProductoSalida::where('producto_id', $almacenRegistro->producto_id)
                    ->whereBetween('fecha_reporte', [$fechaInicial, $fechaFinal])
                    ->whereHas('compra', function ($query) use ($fechaInicial) {
                        $query->where('fecha_compra', '<', $fechaInicial); // Menor a la fecha inicial
                    })
                    ->sum('cantidad');

                dd($sumaStockInicialUtilizado);
            }
        }
        if ($kardexProductoNegro) {
            $stockPorUsar = $kardexProductoNegro->stock_inicial;
            if ($kardexProductoNegro->stock_inicial > 0) {
                //verificar que el stock inicial haya sido utilizado
                $fechaInicial = $kardexNegro->fecha_inicial;
                $fechaFinal = $kardexNegro->fecha_final;
                $sumaStockInicialUtilizado = AlmacenProductoSalida::where('producto_id', $almacenRegistro->producto_id)
                    ->whereBetween('fecha_reporte', [$fechaInicial, $fechaFinal])
                    ->whereHas('compra', function ($query) use ($fechaInicial) {
                        $query->where('fecha_compra', '<', $fechaInicial); // Menor a la fecha inicial
                    })
                    ->sum('cantidad');

                dd($sumaStockInicialUtilizado);
            }
        }
        //Iniciamos buscando alguna compra antes de este registro, la compra debe estar sin fecha de termino, con stock disponible
        //en caso no existe ninguna compra, se debe valorar que sea una compra antes de la fecha del kardex
        $primeraCompraValidaBlanco = CompraProducto::whereDate('fecha_compra', '<', $fecha_reporte)
            ->whereBetween('fecha_compra', [$kardexBlanco->fecha_inicial, $kardexBlanco->fecha_final])
            ->orderBy('fecha_compra', 'asc')->first();

        $primeraCompraValidaNegro = CompraProducto::whereDate('fecha_compra', '<', $fecha_reporte)
            ->whereBetween('fecha_compra', [$kardexNegro->fecha_inicial, $kardexNegro->fecha_final])
            ->orderBy('fecha_compra', 'asc')->first();

        if (!$primeraCompraValidaBlanco && !$primeraCompraValidaNegro) {
            //en caso no hay una compra valida, buscar alguna compra anterior a este periodo y en caso no exista, sugerir registrar una compra para compensar
            $errores = false;
            $mensajeError = [];
            if ($kardexProductoBlanco) {

                if ($kardexProductoBlanco->stock_inicial > 0) {
                    $ultimaCompraKardexAnterior = CompraProducto::whereDate('fecha_compra', '<', $fecha_reporte)
                        ->where('fecha_compra', '<', $kardexBlanco->fecha_inicial)
                        ->whereNull('fecha_termino')
                        ->first();

                    if (!$ultimaCompraKardexAnterior) {
                        $errores = true;
                        $mensajeError[] = "Debe registrar una compra blanco con fecha anterior a: " . $kardexBlanco->fecha_inicial . " para hacer uso del stock inicial.";
                    }
                } else {
                    $errores = true;
                    $mensajeError[] = "Debe registrar una compra de tipo blanco con fecha después de: " . $kardexBlanco->fecha_inicial . " y antes de la salida de este producto: " . $fecha_reporte->format('Y-m-d');
                }

            }
            if ($kardexProductoNegro) {
                if ($kardexProductoNegro->stock_inicial > 0) {
                    if ($kardexProductoNegro->stock_inicial > 0) {
                        $ultimaCompraKardexAnterior = CompraProducto::whereDate('fecha_compra', '<', $fecha_reporte)
                            ->where('fecha_compra', '<', $kardexNegro->fecha_inicial)
                            ->whereNull('fecha_termino')
                            ->first();

                        if (!$ultimaCompraKardexAnterior) {
                            $errores = true;
                            $mensajeError[] = "Debe registrar una compra negro con fecha anterior a: " . $kardexNegro->fecha_inicial . " para hacer uso del stock inicial.";
                        }
                    }
                } else {
                    $errores = true;
                    $mensajeError[] = "Debe registrar una compra de tipo negro con fecha después de: " . $kardexNegro->fecha_inicial . " y antes de la salida de este producto: " . $fecha_reporte->format('Y-m-d');
                }

            }
            if ($errores) {
                throw new Exception("Se han encontrado las siguientes soluciones al error: <br/>" . implode(' ó <br/>', $mensajeError));
            }



            throw new Exception("No hay ninguna compra registrada");
        }
    }*/
    public static function resetearStocks(KardexProducto $kardexProducto)
    {
        $comprasProcesadas = CompraSalidaStock::where('kardex_producto_id', $kardexProducto->id)->get();
        foreach ($comprasProcesadas as $compra) {
            $compraProducto = CompraProducto::find($compra->compra_producto_id);
            if ($compraProducto) {
                $compraProducto->update([
                    'fecha_termino' => null
                ]);
            }
            $compra->delete();
            //en un futuro usar trigger
        }
        $kardexProducto->salidasStockUsado()->update([
            'cantidad_kardex_producto_id' => null,
            'cantidad_stock_inicial' => null
        ]);
    }
    public static function registrarSalida($data, KardexProducto $kardexProducto)
    {

        if (!isset($data['producto_id']))
            throw new Exception("El campo producto_id es obligatorio.");

        if (!isset($data['fecha_reporte']))
            throw new Exception("El campo fecha_reporte es obligatorio.");

        $data['campo_nombre'] = isset($data['campo_nombre']) ? $data['campo_nombre'] : null;
        $data['cantidad'] = isset($data['cantidad']) ? $data['cantidad'] : 0;

        $salidaRegistro = AlmacenProductoSalida::where('producto_id', $data['producto_id'])
            ->where('fecha_reporte', $data['fecha_reporte'])
            ->where('campo_nombre', $data['campo_nombre'])
            ->where('cantidad', $data['cantidad'])->first();

        if ($salidaRegistro) {
            $salidaRegistro->delete();
        }

        $cantidadSolicitada = round($data['cantidad'], 3);
        $stockDisponible = 0;

        //verificar si hay stock
        $stockPorUsar = $kardexProducto->stock_inicial;
        if ($stockPorUsar > 0) {

            $cantidadUsada = (float) $kardexProducto->salidasStockUsado()->sum("cantidad_stock_inicial");
            $stockDisponible = round($stockPorUsar - $cantidadUsada, 3);

            if ($cantidadSolicitada <= $stockDisponible) {
                $data['cantidad_kardex_producto_id'] = $kardexProducto->id;
                $data['cantidad_stock_inicial'] = $cantidadSolicitada;
                return AlmacenProductoSalida::create($data);
            }
        }

        $compras = CompraProducto::whereBetween('fecha_compra', [$kardexProducto->kardex->fecha_inicial, $data['fecha_reporte']])
            ->whereNull('fecha_termino')
            ->where('producto_id', $kardexProducto->producto_id)
            ->where('tipo_kardex', $kardexProducto->kardex->tipo_kardex)
            ->orderBy('fecha_compra', 'asc')
            ->get();

        if ($compras->isEmpty()) {
            throw new Exception("No hay stock disponible para la salida en la fecha: {$data['fecha_reporte']}");
        }


        // Registrar las salidas en las compras
        $stockPorRegistrar = $cantidadSolicitada;
        $stockExcedente = $stockDisponible;

        $stockTodasCompras = 0;
        $detalleStock = "Stock inicial: {$stockExcedente}\n";
        foreach ($compras as $compra) {
            $stockTodasCompras += round($compra->cantidadDisponible, 3);
            $detalleStock .= "Compra ID: {$compra->id}, Fecha: {$compra->fecha_compra}, Stock disponible: {$compra->cantidadDisponible}\n";
        }

        $stockDisponible = $stockTodasCompras + $stockExcedente;

        if ($stockPorRegistrar > $stockDisponible) {
            throw new Exception("No hay stock suficiente. Detalles:\n" . $detalleStock);
        }

        $almacenSalida = AlmacenProductoSalida::create($data);
        if ($stockExcedente > 0) {
            $almacenSalida->cantidad_kardex_producto_id = $kardexProducto->id;
            $almacenSalida->cantidad_stock_inicial = $stockExcedente;
            $almacenSalida->save();
            $stockPorRegistrar -= $stockExcedente;
        }
        foreach ($compras as $compra) {
            if ($stockPorRegistrar > 0) {
                $stockEnCompra = round($compra->cantidadDisponible, 3);
                $usoStock = 0;
                if ($stockEnCompra >= $stockPorRegistrar) {
                    $usoStock = $stockPorRegistrar;

                    CompraSalidaStock::create([
                        'compra_producto_id' => $compra->id,
                        'salida_almacen_id' => $almacenSalida->id,
                        'stock' => $usoStock,
                        'kardex_producto_id' => $kardexProducto->id
                    ]);

                    if (round($stockEnCompra,3) == round($stockPorRegistrar,3)) {
                        $compra->update([
                            'fecha_termino' => $data['fecha_reporte'],
                        ]);
                    }
                    $stockPorRegistrar = 0;

                } else {
                    $usoStock = $stockEnCompra;

                    CompraSalidaStock::create([
                        'compra_producto_id' => $compra->id,
                        'salida_almacen_id' => $almacenSalida->id,
                        'stock' => $usoStock,
                        'kardex_producto_id' => $kardexProducto->id
                    ]);

                    $compra->update([
                        'fecha_termino' => $data['fecha_reporte'],
                    ]);

                    $stockPorRegistrar -= $usoStock;
                }
            }


        }
        /*
        foreach ($compras as $compra) {
            $cantidadCompraDisponible = round($compra->cantidadDisponible, 3);

            if ($cantidadCompraDisponible <= 0) {
                continue;
            }

            if ($salidaTotal <= $stockExcedente + $cantidadCompraDisponible) {
                $usoStock = $salidaTotal - $stockExcedente;

                $salidas[] = CompraSalidaStock::create([
                    'compra_producto_id' => $compra->id,
                    'salida_almacen_id' => null, // Esto se actualizará después
                    'stock' => $usoStock,
                    'kardex_producto_id' => $kardexProducto->id
                ]);

                $compra->update([
                    'fecha_termino' => $usoStock == $cantidadCompraDisponible ? $data['fecha_reporte'] : null,
                ]);

                $stockExcedente = max($stockExcedente + $cantidadCompraDisponible - $salidaTotal, 0);
                $salidaTotal = 0;
                break;
            } else {
                $salidas[] = CompraSalidaStock::create([
                    'compra_producto_id' => $compra->id,
                    'salida_almacen_id' => null,
                    'stock' => $cantidadCompraDisponible,
                    'kardex_producto_id' => $kardexProducto->id
                ]);

                $compra->update(['fecha_termino' => $data['fecha_reporte']]);
                $salidaTotal -= $cantidadCompraDisponible;
            }
        }

        if ($salidaTotal > 0) {

        }

        $almacenSalida = AlmacenProductoSalida::create($data);
        foreach ($salidas as $salida) {
            $salida->update(['salida_almacen_id' => $almacenSalida->id]);
        }

        return $almacenSalida;*/
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
        if ($compraProductoId) {
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
