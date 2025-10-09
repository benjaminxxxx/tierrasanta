<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadActividadProduccion extends Model
{
    protected $table = 'cuad_produccion_actividades';

    protected $fillable = [
        'actividad_bono_id',
        'numero_recojo',
        'produccion'
    ];

    public function actividadBono()
    {
        return $this->belongsTo(CuadActividadBono::class, 'actividad_bono_id');
    }
}
