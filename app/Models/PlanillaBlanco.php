<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanillaBlanco extends Model
{
    protected $table = 'planillas_blanco';
    
    protected $fillable = [
        'id',
        'mes',
        'anio',
        'dias_laborables',
        'total_horas',
        'factor_remuneracion_basica',
        'total_empleados'
    ];
   
    public function detalle()
    {
        return $this->hasMany(PlanillaBlancoDetalle::class, 'planilla_blanco_id')->orderBy('orden');
    }
   
}
