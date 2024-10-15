<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_comercial', 'ingrediente_activo', 'unidad_medida', 'categoria_id'];

    public function getCompraActivaAttribute()
    {
        return $this->compras()->where('estado', 1)->exists();
    }
    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }
    public function compras()
    {
        return $this->hasMany(CompraProducto::class);
    }
}
