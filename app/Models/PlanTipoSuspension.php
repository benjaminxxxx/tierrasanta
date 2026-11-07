<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTipoSuspension extends Model
{
    protected $table = 'plan_tipos_suspension';
    protected $fillable = [
        'codigo',
        'grupo',
        'descripcion',
        'descripcion_corta',
        'incluye_domingos', // los domingos intermedios cuentan como parte de la suspensión
        'duracion_fija', // descanso médico, maternidad…: no se puede regresar antes del fin
    ];
    protected $casts = ['incluye_domingos' => 'boolean', 'duracion_fija' => 'boolean'];
    
}
