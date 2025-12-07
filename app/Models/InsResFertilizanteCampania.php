<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsResFertilizanteCampania extends Model
{
    use HasFactory;

    // Tabla asociada
    protected $table = 'ins_res_fertilizante_campanias';

    // Campos asignables en masa
    protected $fillable = [
        'producto_id',
        'campo_campania_id',
        'fecha',
        'kg',
        'n_kg',
        'p_kg',
        'k_kg',
        'ca_kg',
        'mg_kg',
        'zn_kg',
        'mn_kg',
        'fe_kg',
        'corrector_salinidad_cant',
        'etapa',
    ];

    // Relación con producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con campo de campaña
    public function campoCampania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }
}
