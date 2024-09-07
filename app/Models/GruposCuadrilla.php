<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GruposCuadrilla extends Model
{
    use HasFactory;
    protected $table = 'grupos_cuadrilla';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'color',
        'nombre',
        'costo_dia_sugerido',
        'modalidad_pago'
    ];
}
