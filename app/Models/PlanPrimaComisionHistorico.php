<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Comisiones y primas de una AFP desde un mes (publicación SBS). Se escribe por PlanillaParametroProceso (candados). */
class PlanPrimaComisionHistorico extends Model
{
    protected $fillable = [
        'referencia', 'comision_flujo', 'comision_saldo',
        'prima_seguros', 'aporte_obligatorio',
        'remuneracion_maxima_asegurable', 'fecha_inicio',
        'motivo', 'registrado_por',
    ];
}
