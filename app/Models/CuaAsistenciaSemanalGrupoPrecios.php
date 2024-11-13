<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuaAsistenciaSemanalGrupoPrecios extends Model
{
    use HasFactory;

    protected $table = 'cua_asistencia_semanal_grupo_precios';

    protected $fillable = [
        'cua_asistencia_semanal_grupo_id',
        'cua_asi_sem_id',
        'gru_cua_cod',
        'costo_dia',
        'costo_hora',
        'fecha',
        'cua_asi_sem_cua_id',
    ];

    // Relación con el modelo CuaAsistenciaSemanalGrupo
    public function asistenciaSemanalGrupo()
    {
        return $this->belongsTo(CuaAsistenciaSemanalGrupo::class, 'cua_asistencia_semanal_grupo_id');
    }

    // Relación con el modelo CuaAsistenciaSemanal
    public function asistenciaSemanal()
    {
        return $this->belongsTo(CuaAsistenciaSemanal::class, 'cua_asi_sem_id');
    }

    // Relación con el modelo CuaGrupo
    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'gru_cua_cod', 'codigo');
    }

    // Relación con el modelo Cuadrillero
    public function cuadrillero()
    {
        return $this->belongsTo(CuaAsistenciaSemanalCuadrillero::class, 'cua_asi_sem_cua_id');
    }
}
