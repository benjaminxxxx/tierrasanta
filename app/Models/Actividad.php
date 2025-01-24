<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;
    protected $table='actividades';
    protected $fillable = [
        'fecha',
        'campo',
        'labor_id',
        'horas_trabajadas',
        'labor_valoracion_id',
    ];

    public function labores(){
        return $this->belongsTo(Labores::class, 'labor_id');
    }
    public function valoracion()
    {
        return $this->belongsTo(LaborValoracion::class, 'labor_valoracion_id');
    }
    public function recogidas(){
        return $this->hasMany(Recogidas::class,'actividad_id');
    }
    public function cuadrillero_actividades(){
        return $this->hasMany(CuadrilleroActividad::class,'actividad_id');
    }
    public function getKgAttribute()
    {
        return $this->valoracion?$this->valoracion->kg_8:'-';
    }
}
