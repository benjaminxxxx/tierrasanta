<?php

namespace App\Services;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
use App\Models\KardexProducto;
use Carbon\Carbon;
use Exception;

class AlmacenServicio
{


    public static function obtenerRegistrosPorFecha($mes, $anio, $tipo, $tipoKardex = null)
    {
        $query = AlmacenProductoSalida::with(['distribuciones', 'maquinaria', 'producto']) // Incluir 'producto'
            ->whereMonth('fecha_reporte', $mes)
            ->whereYear('fecha_reporte', $anio);

        // Filtrar por tipo
        if ($tipo === 'combustible') {
            $query->whereHas('producto', function ($q) {
                $q->where('categoria', 'combustible');
            });
        } else {
            $query->whereHas('producto', function ($q) {
                $q->where('categoria', '!=', 'combustible');
            });
        }

        // Filtrar por tipo_kardex si se proporciona
        if (!is_null($tipoKardex)) {
            $query->where('tipo_kardex', $tipoKardex);
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
        } else {
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
            'costo_por_kg',
            'total_costo',
            'cantidad_kardex_producto_id',
            'cantidad_stock_inicial',
            'kardex_producto_id',
            'maquinaria_id',
            'indice',
            'tipo_kardex',
        ];

        $registros = [];
        $codigoCarga = Carbon::now()->format('YmdHis');

        foreach ($data as $registro) {
            $limpio = [];

            foreach ($columnasPermitidas as $columna) {
                $limpio[$columna] = $registro[$columna] ?? null;
            }

            if ($limpio['campo_nombre'] == null) {
                $limpio['campo_nombre'] = '';
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
