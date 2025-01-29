<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumenConsumoProductos extends Model
{
    use HasFactory;

    protected $table = 'resumen_consumo_productos';
    protected $fillable = [
        'fecha',
        'campo',
        'producto',
        'categoria',
        'categoria_id',
        'cantidad',
        'total_costo',
        'campos_campanias_id'
    ];
    public function campania(){
        return $this->belongsTo(CampoCampania::class,'campos_campanias_id');
    }
}
