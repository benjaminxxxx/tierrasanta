<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadrilleroActividad extends Model
{
    protected $table = 'cuadrillero_actividades';
    protected $fillable = [
        'cua_asi_sem_cua_id',
        'actividad_id',
        'total_bono',
        'total_costo',
    ];
    public function recogidas(){
        return $this->hasMany(CuadrilleroActividadRecogida::class,'cuadrillero_actividad_id');
    }
    public function actividad(){
        return $this->belongsTo(Actividad::class,'actividad_id');
    }
}
