<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//antes CuadrillaAsistenciaGrupo
class CuaAsistenciaSemanalGrupo extends Model
{
    use HasFactory;

    // Nombre de la tabla en caso de que sea distinto al plural del modelo
    protected $table = 'cua_asistencia_semanal_grupos';

    // Definición de los campos permitidos para asignación masiva
    protected $fillable = [
        'cua_asi_sem_id',
        'gru_cua_cod',
        'costo_dia',
        'costo_hora',
        'numero_recibo',
        'total_costo',
        'fecha_pagado',
        'dinero_recibido',
        'saldo',
        'total_pagado',
    ];

    // Relación con CuaAsistenciaSemanal
    public function asistenciaSemanal()
    {
        return $this->belongsTo(CuaAsistenciaSemanal::class, 'cua_asi_sem_id');
    }
    public function cuadrillerosEnAsistencia()
    {
        return $this->hasMany(CuaAsistenciaSemanalCuadrillero::class, 'cua_asi_sem_gru_id');
    }
    // Relación con CuaGrupo
    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'gru_cua_cod', 'codigo');
    }
}