<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadTramoLaboralGrupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'cuad_tramo_laboral_id',
        'codigo_grupo',
        'orden',
    ];
    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo', 'codigo');
    }
    public function cuadrilleros()
    {
        return $this->hasMany(CuadTramoLaboralCuadrillero::class, 'cuad_tramo_laboral_grupo_id');
    }
    public function tramoLaboral()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'cuad_tramo_laboral_id');
    }

    
}
