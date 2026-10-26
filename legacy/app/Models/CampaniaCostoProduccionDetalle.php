<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaniaCostoProduccionDetalle extends Model
{
    protected $table = 'campania_costos_produccion_detalles';

    public $timestamps = false;

    protected $fillable = [
        'costo_produccion_id',
        'orden',
        'seccion',
        'grupo',
        'item',
        'codigo',
        'cantidad',
        'costo_soles',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'costo_soles' => 'float',
    ];
}
