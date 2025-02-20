<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribucionCombustible extends Model
{
    use HasFactory;

    protected $table = 'distribucion_combustibles';

    protected $fillable = [
        'fecha',
        'campo',
        'hora_inicio',
        'hora_salida',
        'horas',
        'cantidad_combustible',
        'costo_combustible',
        'actividad',
        'maquinaria_nombre',
        'ratio',
        'valor_costo',
        'maquinaria_id',
        'almacen_producto_salida_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'time',
        'hora_salida' => 'time',
        'cantidad_combustible' => 'decimal:2',
        'costo_combustible' => 'decimal:4',
        'ratio' => 'decimal:4',
        'valor_costo' => 'decimal:4',
    ];

    public function maquinaria()
    {
        return $this->belongsTo(Maquinaria::class);
    }

    public function salidaCombustible()
    {
        return $this->belongsTo(AlmacenProductoSalida::class, 'almacen_producto_salida_id');
    }
}
