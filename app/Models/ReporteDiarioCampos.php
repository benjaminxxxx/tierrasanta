<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiarioCampos extends Model
{
    use HasFactory;
    protected $fillable = [
        'fecha',
        'campos',
        'total_planillas_asistidos',
        'total_faltas',
        'total_vacaciones',
        'total_licencia_maternidad',
        'total_licencia_sin_goce',
        'total_licencia_con_goce',
        'total_descanso_medico',
        'total_atencion_medica',
        'total_cuadrillas',
        'total_planilla',
        'descuento_minutos'
    ];
    public function totales()
    {
        return $this->belongsToMany(TipoAsistencia::class, 'asistencia_planillas_totales','reporte_diario_planilla_id')->withPivot('totales');
    }
}
