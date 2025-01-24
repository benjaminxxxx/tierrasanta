<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadrilleroActividadRecogida extends Model
{
    protected $table='cuadrillero_actividad_recogidas';
    protected $fillable = [
        'cuadrillero_actividad_id',
        'recogida_id',
        'kg_logrados',
        'bono'
    ];
    public function recogida(){
        return $this->belongsTo(Recogidas::class,'recogida_id');
    }
}
