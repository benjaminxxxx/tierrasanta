<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalPoblacionPlantasDetalle extends Model
{
     protected $table = 'eval_poblacion_plantas_detalles';

    protected $fillable = [
        'eval_poblacion_planta_id',
        'numero_cama',
        'longitud_cama',
        'eval_cero_plantas_x_hilera',
        'eval_resiembra_plantas_x_hilera',
    ];

    // -----------------------
    //     RELACIONES
    // -----------------------

    public function evaluacion()
    {
        return $this->belongsTo(EvalPoblacionPlanta::class, 'eval_poblacion_planta_id');
    }

    // ------------------------------------------------------
    //              CAMPOS DINÁMICOS (ACCESSORS)
    // ------------------------------------------------------

    /** Día cero: plantas por metro */
    public function getPlantasPorMetroCeroAttribute()
    {
        if (!$this->longitud_cama || !$this->eval_cero_plantas_x_hilera) {
            return null;
        }

        return $this->eval_cero_plantas_x_hilera / $this->longitud_cama;
    }

    /** Resiembra: plantas por metro */
    public function getPlantasPorMetroResiembraAttribute()
    {
        if (!$this->longitud_cama || !$this->eval_resiembra_plantas_x_hilera) {
            return null;
        }

        return $this->eval_resiembra_plantas_x_hilera / $this->longitud_cama;
    }

    /** Usado para promediar rápido (día cero) */
    public function getPromedioCeroPlantasXHileraAttribute()
    {
        return $this->eval_cero_plantas_x_hilera;
    }

    /** Usado para promediar rápido (resiembra) */
    public function getPromedioResiembraPlantasXHileraAttribute()
    {
        return $this->eval_resiembra_plantas_x_hilera;
    }
}
