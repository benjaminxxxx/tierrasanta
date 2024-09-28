<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiarioRiego extends Model
{
    use HasFactory;

    protected $table = 'reporte_diario_riegos';

    protected $fillable = [
        'campo',
        'hora_inicio',
        'hora_fin',
        'total_horas',
        'documento',
        'fecha',
        'sh',
        'tipo_labor',
        'descripcion',
    ];

}
