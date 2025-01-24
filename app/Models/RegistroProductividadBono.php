<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroProductividadBono extends Model
{
    protected $table="registro_productividad_bonos";
    protected $fillable=[
        'empleado_id',
        'cuadrillero_id',
        'kg_adicional',
        'bono',
        'registro_productividad_id'
    ];
}
