<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadTramoLaboral extends Model
{
    use HasFactory;

    protected $table = 'cuad_tramos_laborales';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'acumula_costos',
        'total_a_pagar',
        'dinero_recibido',
        'saldo',
        'titulo',
        'fecha_hasta_bono',
        'creado_por',
        'actualizado_por'
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
    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->actualizado_por = Auth::id();
            }
        });
    }
}
