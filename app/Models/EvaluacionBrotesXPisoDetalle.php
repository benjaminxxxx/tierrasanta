<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionBrotesXPisoDetalle extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_brotes_x_piso_detalles';

    protected $fillable = [
        'brotes_x_piso_id',
        'numero_cama_muestreada',
        'longitud_cama',
        'brotes_aptos_2p_actual',
        'brotes_aptos_2p_despues_n_dias',
        'brotes_aptos_3p_actual',
        'brotes_aptos_3p_despues_n_dias',
        'brotes_aptos_2p_actual_calculado',
        'brotes_aptos_2p_despues_n_dias_calculado',
        'brotes_aptos_3p_actual_calculado',
        'brotes_aptos_3p_despues_n_dias_calculado',
        'total_actual_de_brotes_aptos_23_piso_calculado',
        'total_de_brotes_aptos_23_pisos_despues_n_dias_calculado',
    ];
    

    public function evaluacion()
    {
        return $this->belongsTo(EvaluacionBrotesXPiso::class, 'brotes_x_piso_id');
    }
}
