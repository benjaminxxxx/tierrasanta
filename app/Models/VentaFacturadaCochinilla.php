<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaFacturadaCochinilla extends Model
{
    protected $table = 'venta_facturada_cochinillas';

    protected $fillable = [
        'fecha_ingreso',
        'campania',
        'campo',
        'area',
        'procedencia',
        'cantidad_fresca',
        'fecha_filtrado',
        'cantidad_seca',
        'condicion',
        'item',
        'conversion_fresco_seco',
        'fecha_venta',
        'comprador',
        'cliente_facturacion',
        'factura_numero',
        'tipo_venta',
        'punto_acido_carminico',
        'acido_carminico',
        'sacos',
        'lote',
        'fecha_despacho',
        'precio_venta_dolar',
        'ingresos_dolar',
        'tipo_cambio',
        'ingresos_soles',
        'grupo_proceso',
        'venta_base_id',
        'contabilizado',
        'origen_datos',
    ];
}
