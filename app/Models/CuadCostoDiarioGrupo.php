<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadCostoDiarioGrupo extends Model
{
    protected $table = 'cuad_costos_diarios_grupos';

    protected $fillable = [
        'codigo_grupo',
        'fecha',
        'jornal',
    ];

    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo', 'codigo');
    }
    public $timestamps = false;
}
