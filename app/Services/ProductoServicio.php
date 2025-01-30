<?php

namespace App\Services;

use App\Models\CompraProducto;
use App\Models\CuadrillaHora;
use Exception;

class ProductoServicio
{

    protected $productoId;
    protected $producto;
    public static function actualizarCompra(CompraProducto $compra, $data)
    {
        /***
         * Se agrego una nueva logica al modificar una compra, el problema es el siguiente
         * Una compra puede ser registrada como blanco y luego ser considerada como negro 
         * por falta de tiempo para el registro contable, entonces:
         * - si ya habia salidas vinculadas al kardex blanco, deben desvincularse, pero
         * - si habia una compra anterior con stock disponible, debe utilizarse ese stock en primer lugar
         * - cuando el stock no sea suficiente, las demas salias deben pasar al nuevo kardex negro
         * - esto solo pasa si 
         * 
         * de compra_salida_stock eliminar segun compra_producto_id=$compra->id
         * en esta tabla se guardan los detalles de cada salida, esto no va a kardex
         */
        dd($compra->id);
        $compra->update($data);

        AlmacenServicio::eliminarRegistrosStocksPosteriores($compra->fecha_compra, $compra->created_at);
    }
    public static function registrarCompra($data)
    {

        try {
            if (!isset($data['producto_id']))
                throw new Exception("El campo producto_id es obligatorio.");

            if (!isset($data['fecha_compra']))
                throw new Exception("El campo fecha_compra es obligatorio.");

            $data['tipo_compra_codigo'] = isset($data['tipo_compra_codigo']) ? str_pad($data['tipo_compra_codigo'], 2, '0', STR_PAD_LEFT) : null;
            $data['serie'] = isset($data['serie']) ? $data['serie'] : null;
            $data['numero'] = isset($data['numero']) ? $data['numero'] : null;

            $compraExiste = CompraProducto::where('serie', $data['serie'])
                ->where('numero', $data['numero'])
                ->where('producto_id', $data['producto_id'])
                ->where('tipo_kardex', $data['tipo_kardex'])
                ->whereDate('fecha_compra', $data['fecha_compra'])
                ->exists();

            if (!$compraExiste) {
                return CompraProducto::create($data);
            } else {
                self::corregirDuplicados($data);
                return $compraExiste;
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public static function corregirDuplicados($data)
    {
        // Buscar duplicados con los mismos criterios
        $duplicados = CompraProducto::where('serie', $data['serie'])
            ->where('numero', $data['numero'])
            ->where('producto_id', $data['producto_id'])
            ->where('tipo_kardex', $data['tipo_kardex'])
            ->whereDate('fecha_compra', $data['fecha_compra'])
            ->get();

        // Si hay más de un registro, eliminar los duplicados y mantener solo uno
        if ($duplicados->count() > 1) {
            $duplicados->skip(1)->each(function ($registro) {
                $registro->delete();
            });
        }
    }
}
