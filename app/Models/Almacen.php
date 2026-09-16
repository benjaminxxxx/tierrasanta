<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Almacen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'almacenes';

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'es_principal',
        'activo',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'almacen_id');
    }
}