<?php

namespace App\Services;

use App\Models\CompraProducto;
use Exception;

class ProductoServicio
{

    protected $productoId;
    protected $producto;
    public static function actualizarCompra(CompraProducto $compra, $data)
    {
        /***
         * Se agrego una nueva logica al modificar una compra:
         * Cuando se genera el kardex la salida se relaciona a la compra mediante CompraSalidaStock
         * todas estas salidas cambiaran de kardex y luego se procedera a borrar su relacion con la compra
         * pero por que se borra la relacion si la compra tambien esta cambiando de kardex?
         * porque al pasarse al kardex diferente puede que haya una compra anterior a esta compra con stock aun disponible, entonces se
         * va a formar una nueva relacion
         */

        $salidaStocks = $compra->almacenSalida;
        if($salidaStocks){
            foreach ($salidaStocks as $salidaStock) {
                $salidaAlmacen = $salidaStock->salida;
                if($salidaAlmacen){
                    $salidaAlmacen->update([
                        'tipo_kardex'=>$data['tipo_kardex'],
                        'costo_por_kg'=>null,
                        'total_costo'=>null,
                    ]);
                    $salidaAlmacen->compraStock()->delete();
                }
            }
        }
        
        $compra->update($data);
    }
    /**
     * Registra cada compra evitando duplicaciones, pasar false en caso no se requiera este filtro
     * @param mixed $comprasArray Array de datos
     * @param mixed $conFiltracionDeDuplicados Para permitir duplicados enviar false, se recomienda dejar vacio para cargas masivas
     * @throws \Exception
     * @return int
     */
    public static function registrarCompraProducto($comprasArray,$conFiltracionDeDuplicados = true)
    {
        if (!is_array($comprasArray) || empty($comprasArray)) {
            throw new Exception("No hay información por guardar");
        }

        // Limpiar y estructurar los datos antes de la inserción
        $registros = self::sanearArray($comprasArray);
        
        // Filtrar registros duplicados antes de insertar
        if($conFiltracionDeDuplicados){
            $registros = self::filtrarDuplicados($registros);
        }
        
        if (!empty($registros)) {
            CompraProducto::insert($registros);
            return count($registros);
        }else{
            return 0;
        }
    }
    public static function sanearArray($data)
    {
        $columnasPermitidas = [
            'producto_id',
            'tienda_comercial_id',
            'fecha_compra',
            'orden_compra',
            'factura',
            'costo_por_kg',
            'total',
            'stock',
            'fecha_termino',
            'tipo_compra_codigo',
            'serie',
            'numero',
            'tabla12_tipo_operacion',
            'tipo_kardex'
        ];

        $registros = [];
        foreach ($data as $registro) {
            $limpio = [];

            foreach ($columnasPermitidas as $columna) {
                $limpio[$columna] = $registro[$columna] ?? null;
            }

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
        $fechas = array_column($registros, 'fecha_compra');
        $fechaMin = min($fechas);
        $fechaMax = max($fechas);

        // Consultar solo los registros en ese rango de fechas
        $existentes = CompraProducto::whereBetween('fecha_compra', [$fechaMin, $fechaMax])->get()->toArray();

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
        return $registro['serie'] . '-' .
            $registro['numero'] . '-' .
            $registro['producto_id'] . '-' .
            $registro['tipo_kardex'] . '-' .
            self::formatearNumero($registro['stock']) . '-' .
            self::formatearNumero($registro['total']) . '-' .
            ($registro['fecha_compra'] ?? 'null');
    }
    private static function formatearNumero($valor)
    {
        return number_format((float) $valor, 3, '.', '');
    }
}
