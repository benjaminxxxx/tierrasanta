<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FertilizacionCampania extends Model
{
    protected $table = "fertilizacion_campanias";
    protected $fillable = [
        'campo_campania_id',
        'producto_id',
        'fecha',
        'kg',
        'kg_ha',
        'n_ha',
        'p_ha',
        'k_ha',
        'ca_ha',
        'mg_ha',
        'zn_ha',
        'mn_ha',
        'fe_ha'
    ];

    public function producto(){
        return $this->belongsTo(Producto::class,'producto_id');
    }
    public function campania(){
        return $this->belongsTo(CampoCampania::class,'campo_campania_id');
    }
}
