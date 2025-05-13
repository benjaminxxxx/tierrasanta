<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInfestacionDetalle extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_infestacion_detalles';

    protected $fillable = [
        'evaluacion_infestacion_id',
        'numero_penca',
        'piso_2',
        'piso_3',
    ];

    /**
     * Relación: este detalle pertenece a una evaluación de infestación
     */
    public function evaluacionInfestacion()
    {
        return $this->belongsTo(EvaluacionInfestacion::class);
    }
}
