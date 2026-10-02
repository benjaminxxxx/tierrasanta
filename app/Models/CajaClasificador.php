<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Clasificador de caja (hoja "Valida"): tipo → clasificador 1 → clasificador 2, con su color de fila. */
class CajaClasificador extends Model
{
    protected $table = 'caja_clasificadores';

    protected $fillable = [
        'tipo', 'grupo', 'clasificador_1', 'clasificador_2',
        'color_fondo', 'color_texto', 'negrita', 'orden', 'activo',
    ];

    protected $casts = [
        'negrita' => 'boolean',
        'activo' => 'boolean',
    ];
}
