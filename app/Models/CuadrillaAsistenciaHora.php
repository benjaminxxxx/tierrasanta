<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadrillaAsistenciaHora extends Model
{
    use HasFactory;
    protected $fillable = [
        'cuadrillero_id',
        'fecha',
        'horas_trabajadas',
    ];

    public function cuadrillero()
    {
        return $this->belongsTo(CuadrillaAsistenciaCuadrillero::class);
    }
}
