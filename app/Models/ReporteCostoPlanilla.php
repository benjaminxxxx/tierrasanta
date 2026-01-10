<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteCostoPlanilla extends Model
{
    protected $table = 'reporte_costo_planillas';
    protected $fillable = [
        'campos_campanias_id',
        'fecha',
        'documento',
        'empleado_nombre',
        'campo',
        'labor',
        'horas_totales',
        'hora_inicio',
        'hora_salida',
        'factor',
        'hora_diferencia',
        'hora_diferencia_entero',
        'costo_hora',
        'gasto',
        'gasto_bono',
    ];
    public function campania(){
        return $this->belongsTo(CampoCampania::class,'campos_campanias_id');
    }
}
