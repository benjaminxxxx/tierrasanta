<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleMaquinariaConsumo extends Model
{
    use HasFactory;
    protected $fillable = [
        'fecha',
        'hora_inicio',
        'hora_salida',
        'total_horas',
        'campo',
        'cantidad_combustible',
        'costo_combustible',
        'descripcion_labor',
        'maquinaria_id',
        'ratio',
        'valor_costo',
        'almacen_producto_salida_id',
    ];
}
