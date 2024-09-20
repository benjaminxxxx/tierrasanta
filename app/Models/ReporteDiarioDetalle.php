<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiarioDetalle extends Model
{
    use HasFactory;
    protected $fillable = [
        'reporte_diario_id',
        'campo',
        'labor',
        'hora_inicio',
        'hora_salida',
    ];

    protected $casts = [
        'hora_inicio' => 'datetime:H:i',
        'hora_salida' => 'datetime:H:i',
    ];
}
