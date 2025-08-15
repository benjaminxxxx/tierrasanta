<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaniaLaboresCosto extends Model
{
    protected $table = 'campania_labores_costos';

    protected $fillable = [
        'codigo_mano_obra',
        'codigo_labor',
        'descripcion_labor',
        'cantidad_ha',
        'costo_ha',
        'costo_total',
        'campo_campania_id'
    ];

    public function getCostoTotalAttribute()
    {
        return $this->cantidad_ha * $this->costo_ha;
    }
}
