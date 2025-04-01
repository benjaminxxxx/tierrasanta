<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoblacionPlantasDetalle extends Model {
    use HasFactory;

    protected $table = 'poblacion_plantas_detalles';

    protected $fillable = [
        'poblacion_plantas_id',
        'cama_muestreada',
        'longitud_cama',
        'plantas_x_cama',
        'plantas_x_metro',
    ];

    public function poblacionPlantas() {
        return $this->belongsTo(PoblacionPlantas::class);
    }
}
