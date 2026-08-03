<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrimaComisionHistorico extends Model
{
    protected $fillable = [
        'referencia', 'comision_flujo', 'comision_saldo',
        'prima_seguros', 'aporte_obligatorio',
        'remuneracion_maxima_asegurable', 'fecha_inicio',
    ];
}
