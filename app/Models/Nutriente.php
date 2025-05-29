<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nutriente extends Model
{
    protected $table = 'nutrientes';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'nombre',
        'unidad',
    ];
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_nutrientes', 'nutriente_codigo', 'producto_id')
            ->withPivot('porcentaje');
    }
    public function productoNutrientes()
    {
        return $this->hasMany(ProductoNutriente::class, 'nutriente_codigo', 'codigo');
    }
}
