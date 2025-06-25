<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaCochinilla extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // PROCESO 1 - REGISTRO RÁPIDO
        'cochinilla_ingreso_id',//nullable
        'grupo_venta',
        'origen_especial',
        'fecha_filtrado',
        'cantidad_seca',
        'condicion',
        'cliente',
        'cliente_facturacion',
        'item',
        'fecha_venta',
        'campo',
        'procedencia',
        'tipo_venta',
        'observaciones',
        'contabilizado',

        // FACTURACIÓN, CALIDAD, INGRESOS
        'factura_numero',
        'precio_venta_dolar',
        'ingresos_dolar',
        'tipo_cambio',
        'ingresos_soles',

        // Calidad
        'punto_acido_carminico',
        'acido_carminico',
        'sacos',

        // Infestador
        'infestador_campo',
        'tipo_infestador',

        // Estados y auditoría interna
        'aprobado_admin',
        'aprobado_facturacion',
        'fecha_aprobacion_admin',
        'fecha_aprobacion_facturacion',
        'aprobador_admin',
        'aprobador_facturacion',
    ];
}
