<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesticidaCampania extends Model
{
    protected $table = "pesticidas_campanias";

    protected $fillable = [
        'campo_campania_id',
        'producto_id',
        'fecha',
        'kg',
        'kg_ha',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }
}
