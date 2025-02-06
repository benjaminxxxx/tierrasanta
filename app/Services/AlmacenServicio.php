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

    public static function obtenerRegistrosPorFecha($mes, $anio, $tipo)
    {
        $query = AlmacenProductoSalida::whereMonth('fecha_reporte', $mes)
            ->whereYear('fecha_reporte', $anio);

        // Filtrar por maquinaria_id según el tipo
        if ($tipo === 'combustible') {
            $query->whereNotNull('maquinaria_id');
        } else {
            $query->whereNull('maquinaria_id');
        }

        return $query->orderBy('fecha_reporte')         // 1. Ordenar por fecha
            ->orderBy('created_at', 'asc')             // 2. Mantener orden de llegada real
            ->orderByRaw('COALESCE(indice, 0) ASC')    // 3. Manejar null en 'indice'
            ->get();
    }


    public static function resetearStocks(KardexProducto $kardexProducto)
    {
        AlmacenProductoSalida::where('cantidad_kardex_producto_id', $kardexProducto->id)->delete();
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
    public static function registrarSalida($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new Exception("No hay información por guardar");
        }

        // Limpiar y estructurar los datos antes de la inserción
        $registros = self::sanearArray($data);

        // Filtrar registros duplicados antes de insertar
        $registrosUnicos = self::filtrarDuplicados($registros);

        if (!empty($registrosUnicos)) {
            AlmacenProductoSalida::insert($registrosUnicos);
            return count($registrosUnicos);
        }else{
            return 0;
        }
    }

    public static function sanearArray($data)
    {
        $columnasPermitidas = [
            'item',
            'producto_id',
            'campo_nombre',
            'cantidad',
            'fecha_reporte',
            'compra_producto_id',
            'costo_por_kg',
            'total_costo',
            'cantidad_kardex_producto_id',
            'cantidad_stock_inicial',
            'kardex_producto_id',
            'maquinaria_id',
            'indice',
            'tipo_kardex'
        ];

        $registros = [];
        $codigoCarga = Carbon::now()->format('YmdHis');

        foreach ($data as $registro) {
            $limpio = [];

            foreach ($columnasPermitidas as $columna) {
                $limpio[$columna] = $registro[$columna] ?? null;
            }
            $limpio['registro_carga'] = $codigoCarga;

            $registros[] = $limpio;
        }

        return $registros;
    }

    public static function filtrarDuplicados($registros)
    {
        if (empty($registros)) {
            return [];
        }

        // Obtener fecha mínima y máxima del lote
        $fechas = array_column($registros, 'fecha_reporte');
        $fechaMin = min($fechas);
        $fechaMax = max($fechas);

        // Consultar solo los registros en ese rango de fechas
        $existentes = AlmacenProductoSalida::whereBetween('fecha_reporte', [$fechaMin, $fechaMax])->get()->toArray();

        // Crear un mapa de registros existentes con clave única
        $existentesMap = [];
        foreach ($existentes as $existente) {
            $clave = self::generarClaveUnica($existente);
            $existentesMap[$clave] = true;
        }

        // Filtrar los registros que NO existen en la base de datos
        return array_filter($registros, function ($registro) use ($existentesMap) {
            return !isset($existentesMap[self::generarClaveUnica($registro)]);
        });

    }
    private static function generarClaveUnica($registro)
    {
        return $registro['producto_id'] . '-' .
            $registro['campo_nombre'] . '-' .
            self::formatearNumero($registro['cantidad']) . '-' .
            $registro['fecha_reporte'] . '-' .
            ($registro['maquinaria_id'] ?? 'null');
    }

    private static function formatearNumero($valor)
    {
        return number_format((float) $valor, 3, '.', '');
    }
    /*
    public static function registrarSalida($data, KardexProducto $kardexProducto)
    {

        if (!isset($data['producto_id']))
            throw new Exception("El campo producto_id es obligatorio.");

        if (!isset($data['fecha_reporte']))
            throw new Exception("El campo fecha_reporte es obligatorio.");

        $data['campo_nombre'] = isset($data['campo_nombre']) ? $data['campo_nombre'] : null;
        $data['cantidad'] = isset($data['cantidad']) ? $data['cantidad'] : 0;
        $data['kardex_producto_id'] = $kardexProducto->id;
        $data['maquinaria_id'] = isset($data['maquinaria_id']) ? $data['maquinaria_id'] : null;

        $salidaRegistro = AlmacenProductoSalida::where('producto_id', $data['producto_id'])
            ->where('fecha_reporte', $data['fecha_reporte'])
            ->where('campo_nombre', $data['campo_nombre'])
            ->where('maquinaria_id', $data['maquinaria_id'])
            ->where('cantidad', $data['cantidad'])->first();

        if ($salidaRegistro) {
            if ($salidaRegistro->PerteneceAUnaCompra && $salidaRegistro->precio_por_kg) {
                return;
            }
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

        //reemplazar en mantenimiento por KardexProducto::stockDisponible(FECHA)
        $compras = CompraProducto::whereBetween('fecha_compra', [$kardexProducto->kardex->fecha_inicial, $data['fecha_reporte']])
            ->whereNull('fecha_termino')
            ->where('producto_id', $kardexProducto->producto_id)
            ->where('tipo_kardex', $kardexProducto->kardex->tipo_kardex)
            ->orderBy('fecha_compra', 'asc')
            ->get();

        if ($compras->isEmpty()) {
            $dataString = self::formatArrayToKeyValueString($data);
            throw new Exception("No hay stock disponible para la salida en la fecha: {$data['fecha_reporte']}\n{$dataString}");
        }


        // Registrar las salidas en las compras
        $stockPorRegistrar = $cantidadSolicitada;
        $stockExcedente = $stockDisponible;
        /////////////////////////////////////////

        $stockTodasCompras = 0;
        $detalleStock = "Stock inicial: {$stockExcedente}\n";
        foreach ($compras as $compra) {
            $stockTodasCompras += round($compra->cantidadDisponible, 3);
            $detalleStock .= "Compra ID: {$compra->id}, Fecha: {$compra->fecha_compra}, Stock disponible: {$compra->cantidadDisponible}\n";
        }

        $stockDisponible = $stockTodasCompras + $stockExcedente;

        if (round($stockPorRegistrar,3) > round($stockDisponible,3)) {
            throw new Exception("No hay stock suficiente:" .$stockPorRegistrar. " es mayor a ".$stockDisponible.". Detalles:\n" . $detalleStock);
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

                    if (round($stockEnCompra, 3) == round($stockPorRegistrar, 3)) {
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
    }*/
    public static function formatArrayToKeyValueString(array $data): string
    {
        $formatted = '';
        foreach ($data as $key => $value) {
            $formatted .= "{$key}: {$value}\n";
        }
        return $formatted;
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

        if ($registro->PerteneceAUnaCompra) {
            $compras = $registro->compraStock()->get();
            foreach ($compras as $regstroCompaStock) {
                $compra = CompraProducto::find($regstroCompaStock->compra_producto_id);
                if ($compra) {
                    self::resetearFechaTermino($compra);
                }
            }
        }

        $registro->delete();
    }
    public static function eliminarRegistrosStocksPosteriores($fecha1, $fecha2, $productoId)
    {
        $salidasPosteriores = AlmacenProductoSalida::whereDate('fecha_reporte', '>=', $fecha1)
            ->whereDate('created_at', '>=', $fecha2)
            ->where('producto_id', $productoId)
            ->get();

        foreach ($salidasPosteriores as $salida) {
            $salida->costo_por_kg = null;
            $salida->total_costo = null;
            $salida->save();

            $compras = $salida->compraStock()->get();
            foreach ($compras as $regstroCompaStock) {
                $compra = CompraProducto::find($regstroCompaStock->compra_producto_id);
                $regstroCompaStock->delete();
                if ($compra) {
                    self::resetearFechaTermino($compra);
                }
            }
        }
    }
    public static function resetearFechaTermino(CompraProducto $compra)
    {
        if ($compra) {
            /**
             * Original
             */
            /*
            if ($compra->CantidadDisponible <= 0) {
                $compra->fecha_termino = null;
                $compra->save();
            }*/
            //Nuevo
            if ($compra->CantidadDisponible > 0) {
                $compra->fecha_termino = null;
                $compra->save();
            }
        }
    }
}
