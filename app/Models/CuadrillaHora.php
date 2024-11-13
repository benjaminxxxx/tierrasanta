<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadrillaHora extends Model
{
    use HasFactory;

    protected $table = 'cuadrilla_horas';

    protected $fillable = [
        'cua_asi_sem_cua_id',
        'fecha',
        'horas',
        'bono',
        'costo_dia',
    ];

    // Relación con la tabla CuaAsistenciaSemanalCuadrilleros
    public function asistenciaSemanalCuadrillero()
    {
        return $this->belongsTo(CuaAsistenciaSemanalCuadrillero::class, 'cua_asi_sem_cua_id');
    }
}
