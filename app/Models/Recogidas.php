<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recogidas extends Model
{
    use HasFactory;
    protected $table='recogidas';
    protected $fillable = [
        'actividad_id',
        'recogida_numero',
        'horas',
        'kg_estandar'
    ];
}
