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
        'fecha_eval_resiembra',

    ];
    protected $appends = [
        'promedio_brazos2_hilera_cero',
        'promedio_brazos2_metro_cero',
        'promedio_brazos3_hilera_cero',
        'promedio_brazos3_metro_cero',
        'total_brazos2_ha_cero',
        'total_brazos3_ha_cero',
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
        if (!$this->metros_cama_ha)
            return null;

        return $this->promedio_plantas_metro_cero * $this->metros_cama_ha;
    }

    /** Total brazos 2° piso por ha — Día Cero */
    public function getTotalBrazos2HaCeroAttribute()
    {
        if (!$this->metros_cama_ha)
            return null;

        return $this->promedio_brazos2_metro_cero * $this->metros_cama_ha;
    }

    /** Total brazos 3° piso por ha — Día Cero */
    public function getTotalBrazos3HaCeroAttribute()
    {
        if (!$this->metros_cama_ha)
            return null;

        return $this->promedio_brazos3_metro_cero * $this->metros_cama_ha;
    }

    /** Promedio plantas por ha — Resiembra */
    public function getPromedioPlantasHaResiembraAttribute()
    {
        if (!$this->metros_cama_ha)
            return null;

        return $this->promedio_plantas_metro_resiembra * $this->metros_cama_ha;
    }

    // Promedio de brazos2_piso_x_hilera_cero entre todos los detalles
    public function getPromedioBrazos2HileraCeroAttribute(): float
    {
        return $this->detalles->avg('brazos2_piso_x_hilera_cero') ?? 0;
    }

    // Promedio de brazos2_piso_x_metro_cero (accessor del detalle)
    public function getPromedioBrazos2MetroCeroAttribute(): float
    {
        return $this->detalles->avg('brazos2_piso_x_metro_cero') ?? 0;
    }

    // Promedio de brazos3_piso_x_hilera_cero
    public function getPromedioBrazos3HileraCeroAttribute(): float
    {
        return $this->detalles->avg('brazos3_piso_x_hilera_cero') ?? 0;
    }

    // Promedio de brazos3_piso_x_metro_cero (accessor del detalle)
    public function getPromedioBrazos3MetroCeroAttribute(): float
    {
        return $this->detalles->avg('brazos3_piso_x_metro_cero') ?? 0;
    }

}
