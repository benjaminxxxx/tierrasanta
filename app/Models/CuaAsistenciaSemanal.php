<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//antes CuadrillaAsistencia
class CuaAsistenciaSemanal extends Model
{
    use HasFactory;
    protected $table = "cua_asistencia_semanal";
    protected $fillable = ['titulo', 'fecha_inicio', 'fecha_fin', 'total','estado'];

    public function grupos(){
        return $this->hasMany(CuaAsistenciaSemanalGrupo::class,'cua_asi_sem_id');
    }
}
