<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadrillaAsistencia extends Model
{
    use HasFactory;
    protected $fillable = ['titulo', 'fecha_inicio', 'fecha_fin', 'total','estado'];

    public function grupos()
    {
        return $this->hasMany(CuadrillaAsistenciaGrupo::class);
    }

    public function cuadrilleros()
    {
        return $this->hasMany(CuadrillaAsistenciaCuadrillero::class);
    }
}
