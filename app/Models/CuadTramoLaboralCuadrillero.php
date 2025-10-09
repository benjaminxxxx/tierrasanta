<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadTramoLaboralCuadrillero extends Model
{
    use HasFactory;

    protected $table = 'cuad_tramo_cuadrilleros';

    protected $fillable = [
        'cuadrillero_id',
        'cuad_tramo_laboral_grupo_id',
        'orden',
        'nombres'
    ];

    // Relaciones
    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }

    public function tramoLaboralGrupal()
    {
        return $this->belongsTo(CuadTramoLaboralGrupo::class, 'cuad_tramo_laboral_grupo_id');
    }
}
