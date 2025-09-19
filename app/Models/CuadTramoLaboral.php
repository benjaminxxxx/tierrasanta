<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadTramoLaboral extends Model
{
    use HasFactory;

    protected $table = 'cuad_tramo_laborals';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'acumula_costos',
        'total_a_pagar',
        'dinero_recibido',
        'saldo',
        'titulo',
    ];
    public function gruposEnTramos(){
        return $this->hasMany(CuadTramoLaboralGrupo::class,'cuad_tramo_laboral_id');
    }
    public function grupos()
    {
        return $this->hasManyThrough(
            CuaGrupo::class,
            CuadTramoLaboralGrupo::class,
            'cuad_tramo_laboral_id',
            'codigo',            
            'id',                    
            'codigo_grupo'           
        );
    }
}
