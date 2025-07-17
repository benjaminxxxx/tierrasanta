<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;
    protected $table = 'actividades';
    /*
    Antigua estructura, ahora con mas campos
    protected $fillable = [
        'fecha',
        'campo',
        'labor_id',
        'horas_trabajadas',
        'labor_valoracion_id',
    ];*/
    protected $fillable = [
        'fecha',
        'campo',
        'labor_id',
        'nombre_labor',
        'codigo_labor',
        //'horarios',
        'tramos_bonificacion',
        'estandar_produccion',
        'total_horas',
        'unidades',
        'created_by',
    ];


    public function labores()
    {
        return $this->belongsTo(Labores::class, 'labor_id');
    }
    /*obsoleto sistema de valoraciones modificado, ahora labores tiene campos donde se indica las nuevas valoraciones
    public function valoracion()
    {
        return $this->belongsTo(LaborValoracion::class, 'labor_valoracion_id');
    }*/
    public function recogidas()
    {
        return $this->hasMany(Recogidas::class, 'actividad_id');
    }
    public function cuadrillero_actividades()
    {
        return $this->hasMany(CuadrilleroActividad::class, 'actividad_id');
    }
    /*obsoleto por cambiar
    public function getKgAttribute()
    {
        return $this->valoracion ? $this->valoracion->kg_8 : '-';
    }*/

    protected $casts = [
        'horarios' => 'array',
        'tramos_bonificacion' => 'array',
    ];

}
