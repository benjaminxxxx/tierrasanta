<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Maquinaria extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'alias_blanco',
    ];

    /**
     * Relación con el modelo DetalleMaquinariaConsumo.
     *
     * Una maquinaria puede tener múltiples consumos de detalle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function detallesConsumo()
    {
        return $this->hasMany(DetalleMaquinariaConsumo::class);
    }

    /**
     * Relación con el modelo AlmacenProductoSalida.
     *
     * Una maquinaria puede estar asociada a múltiples salidas de productos del almacén.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salidasAlmacen()
    {
        return $this->hasMany(AlmacenProductoSalida::class);
    }
}
