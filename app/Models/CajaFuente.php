<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lugar donde está repartido el dinero de caja (AQP, Naranja, Aqp-Flavia, Cuadrillas…). */
class CajaFuente extends Model
{
    protected $table = 'caja_fuentes';

    protected $fillable = ['nombre', 'orden', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
