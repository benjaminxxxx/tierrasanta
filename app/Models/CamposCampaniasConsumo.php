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
        'categoria_id',
        'monto',
        'reporte_file'
    ];
    public function categoriaProducto(){
        return $this->belongsTo(CategoriaProducto::class,'categoria_id');
    }
    public function campania(){
        return $this->belongsTo(CampoCampania::class,'campos_campanias_id');
    }
}
