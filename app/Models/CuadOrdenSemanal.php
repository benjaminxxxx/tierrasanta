<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadOrdenSemanal extends Model
{
    protected $table = 'cuad_orden_semanal';

    protected $fillable = [
        'cuadrillero_id',
        'cuad_tramo_laboral_id',
        'orden',
        'codigo_grupo'
    ];

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }
}
