<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadGrupoOrden extends Model
{
    protected $table = 'cuad_grupo_orden';

    public $incrementing = false; // porque usamos clave compuesta

    protected $keyType = 'string'; // uno de los keys es string

    protected $fillable = [
        'fecha',
        'codigo_grupo',
        'orden',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo', 'codigo');
    }
}
