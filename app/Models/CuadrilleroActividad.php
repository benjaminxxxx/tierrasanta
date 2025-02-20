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
    public function cuadrilleroSemana(){
        return $this->belongsTo(CuaAsistenciaSemanalCuadrillero::class,'cua_asi_sem_cua_id');
    }
    public function actividad(){
        return $this->belongsTo(Actividad::class,'actividad_id');
    }
    public function labor()
    {
        return $this->hasOneThrough(
            Labores::class,      // Modelo final
            Actividad::class,  // Modelo intermedio
            'id',              // Llave primaria de Actividad
            'id',              // Llave primaria de Labor
            'actividad_id',    // FK en CuadrilleroActividad que apunta a Actividad
            'labor_id'         // FK en Actividad que apunta a Labor
        );
    }
    public function cuadrillero()
    {
        return $this->hasOneThrough(
            Cuadrillero::class,                        // Modelo final
            CuaAsistenciaSemanalCuadrillero::class,    // Modelo intermedio
            'id',                                      // Llave primaria de CuaAsistenciaSemanalCuadrillero
            'id',                                      // Llave primaria de Cuadrillero
            'cua_asi_sem_cua_id',                      // FK en CuadrilleroActividad que apunta a CuaAsistenciaSemanalCuadrillero
            'cua_id'                                   // FK en CuaAsistenciaSemanalCuadrillero que apunta a Cuadrillero
        );
    }
    public function recogidas(){
        return $this->hasMany(CuadrilleroActividadRecogida::class,'cuadrillero_actividad_id');
    }
    
}
