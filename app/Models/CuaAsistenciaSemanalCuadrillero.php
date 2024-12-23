<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//antes CuadrillaAsistenciaCuadrillero
class CuaAsistenciaSemanalCuadrillero extends Model
{
    use HasFactory;

    // Nombre de la tabla en caso de que sea distinto al plural del modelo
    protected $table = 'cua_asistencia_semanal_cuadrilleros';

    // Definición de los campos permitidos para asignación masiva
    protected $fillable = [
        'cua_id',
        'cua_asi_sem_gru_id',
        'monto_recaudado'
    ];

    // Relación con Cuadrillero
    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class, 'cua_id');
    }

    // Relación con CuaAsistenciaSemanalGrupo
    public function asistenciaSemanalGrupo()
    {
        return $this->belongsTo(CuaAsistenciaSemanalGrupo::class, 'cua_asi_sem_gru_id');
    }
}
