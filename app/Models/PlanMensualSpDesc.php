<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanMensualSpDesc extends Model
{
    protected $table = 'plan_mensual_sp_desc';

    protected $fillable = [
        'plan_mensual_id',
        'codigo',
        'referencia',
        'tipo',
        'comision',
        'prima_seguros',
        'aporte_obligatorio',
        'porcentaje',
        'porcentaje_65',
    ];
}
