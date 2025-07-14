<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadOrdenSemanal extends Model
{
    protected $table = 'cuad_orden_semanal';

    protected $fillable = [
        'cuadrillero_id',
        'fecha_inicio',
        'orden',
    ];

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }
}
