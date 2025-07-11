<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuadGrupoCuadrilleroFecha extends Model
{
    use HasFactory;

    protected $table = 'cuad_grupo_cuadrillero_fechas';

    protected $fillable = [
        'codigo_grupo',
        'cuadrillero_id',
        'fecha',
    ];

    // Relaciones opcionales (si las necesitas)
    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }

    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo', 'codigo');
    }
}
