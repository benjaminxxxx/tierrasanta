<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboresRiego extends Model
{
    use HasFactory;

    protected $table = 'reg_labores';

    protected $fillable = [
        'nombre_labor',
        'es_riego',
        'es_apoyo_riego',
        'consumo_m3_hora',
    ];
    protected $casts = [
        'es_riego' => 'boolean',
        'es_apoyo_riego' => 'boolean',
        'consumo_m3_hora' => 'decimal:2',
    ];
}
