<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanTipoAsistencia extends Model
{
    use HasFactory;

    protected $table = 'plan_tipo_asistencias';

    protected $fillable = [
        'codigo',
        'descripcion',
        'horas_jornal',
        'color',
        'tipo',
        'afecta_sueldo',
        'porcentaje_remunerado',
        'requiere_documento',
        'acumula_vacaciones',
        'acumula_asistencia',
        'activo',
    ];
    public function getAcumulaAsistenciaLabelAttribute()
    {
        return $this->acumula_asistencia ? 'SI' : 'NO';
    }
}
