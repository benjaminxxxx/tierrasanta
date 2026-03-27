<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalPoblacionPlantasDetalle extends Model
{
    protected $table = 'eval_poblacion_plantas_detalles';

    protected $fillable = [
        'eval_poblacion_planta_id',
        'numero_cama',
        'longitud_cama', //longitud de la cama en metros
        'eval_cero_plantas_x_hilera',
        'eval_resiembra_plantas_x_hilera',
        //nuevos
        'brazos2_piso_x_hilera_cero',
        'brazos3_piso_x_hilera_cero'
    ];

    protected $appends = [
        'plantas_por_metro_cero',
        'plantas_por_metro_resiembra',
        'brazos2_piso_x_metro_cero',
        'brazos3_piso_x_metro_cero',
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

    /** Día cero: brazos 2° piso por metro */
    public function getBrazos2PisoXMetroCeroAttribute()
    {
        if (!$this->longitud_cama || $this->brazos2_piso_x_hilera_cero === null) {
            return null;
        }

        return $this->brazos2_piso_x_hilera_cero / $this->longitud_cama;
    }

    /** Día cero: brazos 3° piso por metro */
    public function getBrazos3PisoXMetroCeroAttribute()
    {
        if (!$this->longitud_cama || $this->brazos3_piso_x_hilera_cero === null) {
            return null;
        }

        return $this->brazos3_piso_x_hilera_cero / $this->longitud_cama;
    }
}
