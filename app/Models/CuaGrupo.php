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
    public function cuadrilleros()
    {
        return $this->hasMany(Cuadrillero::class, 'codigo_grupo', 'codigo');
    }
    public function fechasCuadrilleros()
    {
        return $this->hasMany(CuadGrupoCuadrilleroFecha::class, 'codigo_grupo', 'codigo');
    }
    public function getFechasTrabajadasAttribute()
    {
        return $this->fechasCuadrilleros()
            ->select('fecha')
            ->distinct()
            ->count('fecha');
    }
}
