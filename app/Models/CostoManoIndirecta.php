<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostoManoIndirecta extends Model
{
    use HasFactory;

    protected $fillable = [
        'mes',
        'anio',

        // Cuadrillero
        'blanco_cuadrillero_monto',
        'blanco_cuadrillero_file',
        'negro_cuadrillero_monto',
        'negro_cuadrillero_file',

        // Planillero
        'blanco_planillero_monto',
        'blanco_planillero_file',
        'negro_planillero_monto',
        'negro_planillero_file',

        // Maquinaria
        'blanco_maquinaria_monto',
        'blanco_maquinaria_file',
        'negro_maquinaria_monto',
        'negro_maquinaria_file',

        // Maquinaria con salida
        'blanco_maquinaria_salida_monto',
        'blanco_maquinaria_salida_file',
        'negro_maquinaria_salida_monto',
        'negro_maquinaria_salida_file',

        // Costos adicionales
        'blanco_costos_adicionales_monto',
        'blanco_costos_adicionales_file',
        'negro_costos_adicionales_monto',
        'negro_costos_adicionales_file',
    ];
}
