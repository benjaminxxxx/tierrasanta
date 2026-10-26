<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CamposCampaniasConsumo extends Model
{
    use HasFactory;
    protected $table = 'campos_campanias_consumos';
    protected $fillable = [
        'campos_campanias_id',
        'categoria',
        'monto',
        'reporte_file'
    ];

    public function campania(){
        return $this->belongsTo(CampoCampania::class,'campos_campanias_id');
    }
}
