<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaCochinilla extends Model
{
    protected $fillable = [
        'cochinilla_ingreso_id',
        'grupo_venta',
        'fecha_filtrado',
        'cantidad_seca',
        'condicion',
        'cliente',
        'item',
        'fecha_venta',
        'campo',
        'observaciones',
        'aprobado_facturacion',
        'fecha_aprobacion_facturacion',
        'aprobador_facturacion',
    ];
    
    public function ingreso()
    {
        return $this->belongsTo(CochinillaIngreso::class, 'cochinilla_ingreso_id');
    }
}
