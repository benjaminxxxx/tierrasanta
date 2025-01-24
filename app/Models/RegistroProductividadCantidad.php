<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroProductividadCantidad extends Model
{
    protected $table="registro_productividad_cantidads";
    protected $fillable=[
        'empleado_id',
        'cuadrillero_id',
        'kg',
        'kg_subtotal',
        'registro_productividad_detalles_id'
    ];
}
