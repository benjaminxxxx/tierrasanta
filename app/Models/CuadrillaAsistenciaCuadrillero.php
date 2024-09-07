<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadrillaAsistenciaCuadrillero extends Model
{
    use HasFactory;
    protected $fillable = [
        'cuadrilla_asistencia_id',
        'nombres',
        'identificador',
        'dni',
        'codigo_grupo',
        'monto_recaudado',
        'planilla'
    ];

    public function asistencia()
    {
        return $this->belongsTo(CuadrillaAsistencia::class);
    }

    public function grupo()
    {
        return $this->belongsTo(CuadrillaAsistenciaGrupo::class, 'codigo_grupo', 'codigo');
    }
}
