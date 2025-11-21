<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalPoblacionPlanta extends Model
{
    protected $table = 'eval_poblacion_plantas';

    protected $fillable = [
        'area_lote',
        'fecha_siembra',
        'evaluador',
        'metros_cama_ha',
        'campania_id',
        'fecha_eval_cero',
        'fecha_eval_resiembra'
    ];

    // -----------------------
    //     RELACIONES
    // -----------------------

    public function detalles()
    {
        return $this->hasMany(EvalPoblacionPlantasDetalle::class, 'eval_poblacion_planta_id');
    }

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campania_id');
    }

    // ------------------------------------------------------
    //     CALCULOS DINÁMICOS — PROMEDIOS GLOBALES POR MODELO
    // ------------------------------------------------------

    /** Promedio global Día Cero (plantas x hilera) */
    public function getPromedioDiaCeroAttribute()
    {
        return $this->detalles->avg('eval_cero_plantas_x_hilera');
    }

    /** Promedio global Resiembra (plantas x hilera) */
    public function getPromedioResiembraAttribute()
    {
        return $this->detalles->avg('eval_resiembra_plantas_x_hilera');
    }

    /** Promedio plantas por metro — Día Cero */
    public function getPromedioPlantasMetroCeroAttribute()
    {
        return $this->detalles->avg('plantas_por_metro_cero');
    }

    /** Promedio plantas por metro — Resiembra */
    public function getPromedioPlantasMetroResiembraAttribute()
    {
        return $this->detalles->avg('plantas_por_metro_resiembra');
    }

    /** Promedio plantas por ha — Día cero */
    public function getPromedioPlantasHaCeroAttribute()
    {
        if (!$this->metros_cama_ha) return null;

        return $this->promedio_plantas_metro_cero * $this->metros_cama_ha;
    }

    /** Promedio plantas por ha — Resiembra */
    public function getPromedioPlantasHaResiembraAttribute()
    {
        if (!$this->metros_cama_ha) return null;

        return $this->promedio_plantas_metro_resiembra * $this->metros_cama_ha;
    }
}
