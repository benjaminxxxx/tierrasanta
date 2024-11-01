<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//antes GruposCuadrilla
class CuaGrupo extends Model
{
    protected $table = 'cua_grupos';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'color',
        'nombre',
        'costo_dia_sugerido',
        'modalidad_pago',
        'estado'
    ];
}
