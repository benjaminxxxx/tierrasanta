<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyeccionRendimientoPoda extends Model
{
    use HasFactory;
    protected $table = 'proyeccion_rendimiento_podas';
    protected $fillable = [
        'campo_campania_id',
        'nro_muestra',
        'peso_fresco_kg',
        'peso_seco_kg',
        'rdto_hectarea_kg',
        'relacion_fresco_seco',
    ];
}
