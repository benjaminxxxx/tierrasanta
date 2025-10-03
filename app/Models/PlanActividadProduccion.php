<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanActividadProduccion extends Model
{
    use HasFactory;

    protected $table = 'plan_actividad_produccions';

    protected $fillable = [
        'actividad_bono_id',
        'numero_recojo',
        'produccion',
    ];

    // Relaciones
    public function actividadBono()
    {
        return $this->belongsTo(PlanActividadBono::class, 'actividad_bono_id');
    }
}
