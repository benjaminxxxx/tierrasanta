<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaCochinilla extends Model
{
     use SoftDeletes;

     protected $fillable = [
        'fecha_ingreso',
        'fecha_filtrado',
        'area',
        'fecha_venta',
        'nombre_comprador',
        'tipo_venta',
        'factura_numero',
        'lote',
        'kg',
        'campo',
        'procedencia',
        'precio_venta_dolar',
        'punto_acido_carminico',
        'acido_carminico',
        'sacos',
        'ingresos_dolar',
        'tipo_cambio',
        'ingresos_soles',
        'estado',
        'infestador_campo',
        'tipo_infestador',
        'observaciones',
        'cantidad_seca',
        'condicion',
    ];
}
