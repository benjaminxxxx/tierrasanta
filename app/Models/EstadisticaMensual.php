<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadisticaMensual extends Model
{
    protected $table = 'estadistica_mensuales';

    protected $fillable = [
        'mes',
        'anio',
        'clave',
        'valor',
        'valor_anterior',
    ];

    protected $casts = [
        'mes'   => 'integer',
        'anio'  => 'integer',
    ];
}
