<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiendaComercial extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'ruc',
        'contacto'
    ];

    // Relación con Compras
    public function compras()
    {
        return $this->hasMany(CompraProducto::class, 'tienda_comercial_id');
    }
}
