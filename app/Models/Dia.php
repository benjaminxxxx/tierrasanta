<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dia extends Model
{
    use HasFactory;
    protected $fillable = [
        'empleado_id',
        'dia',
        'mes',
        'anio',
        'es_dia_no_laborable',
        'es_dia_domingo',
        'observaciones'
    ];
}
