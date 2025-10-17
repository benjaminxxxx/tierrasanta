<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanResumenDiario extends Model
{
    use HasFactory;
    protected $table = 'plan_resumen_diario';
    protected $fillable = [
        'fecha',
        'total_actividades',
        'total_cuadrillas',
        'total_planilla',
        'resumen_cuadrilla'
    ];
    public function totales()
    {
        return $this->hasMany(PlanResumenDiarioTipoAsistencia::class, 'plan_res_dia_id');
    }
}
