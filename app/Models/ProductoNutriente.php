<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoNutriente extends Model
{
    protected $table = 'producto_nutrientes';

    protected $fillable = [
        'producto_id',
        'nutriente_codigo',
        'porcentaje',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function nutriente()
    {
        return $this->belongsTo(Nutriente::class, 'nutriente_codigo', 'codigo');
    }
}
