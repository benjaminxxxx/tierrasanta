<?php

namespace App\Services;

use App\Models\CompraProducto;
use App\Models\CuadrillaHora;
use Exception;

class ProductoServicio
{

    protected $productoId;
    protected $producto;
    public function __construct()
    {
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
