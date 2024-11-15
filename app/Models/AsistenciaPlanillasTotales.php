<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsistenciaPlanillasTotales extends Model
{
    use HasFactory;
    protected $table = 'asistencia_planillas_totales';
    protected $fillable = [
        'tipo_asistencia_id',
        'reporte_diario_planilla_id',
        'total',
    ];
}
