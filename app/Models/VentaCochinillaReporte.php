<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaCochinillaReporte extends Model
{
    use HasFactory;

    protected $fillable = [
        'cochinilla_ingreso_id',

        'cosecha_fecha_ingreso',
        'cosecha_campo',
        'cosecha_procedencia',
        'cosecha_cantidad_fresca',

        'proceso_fecha_filtrado',
        'proceso_cantidad_seca',
        'proceso_condicion',

        'venta_fecha_venta',
        'venta_comprador',
        'venta_infestadores_del_campo',

        'cosecha_encontrada',
        'fusionada',
    ];

    protected $casts = [
        'cosecha_encontrada' => 'boolean',
        'fusionada' => 'boolean',
        'cosecha_fecha_ingreso' => 'date',
        'proceso_fecha_filtrado' => 'date',
        'venta_fecha_venta' => 'date',
    ];
}
