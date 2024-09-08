<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorasAcumuladas extends Model
{
    use HasFactory;
    protected $fillable = [
        'documento',
        'fecha_acumulacion',
        'fecha_uso',
        'minutos_acomulados'
    ];
}
