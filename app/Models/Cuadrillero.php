<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Cuadrillero extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuad_cuadrilleros';

    protected $fillable = [
        'nombres',
        'dni',
        'codigo_grupo',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];
    public function registrosDiarios()
    {
        return $this->hasMany(CuadRegistroDiario::class);
    }
    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo');
    }
    public function getGrupoActualAttribute()
    {
        return $this->grupo?->nombre ?? '-';
    }
    public function getNombreCompletoAttribute()
    {
        return $this->nombres . ($this->dni ? ' - ' . $this->dni : '');
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

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->eliminado_por = Auth::id();
                $model->saveQuietly(); // guarda sin volver a disparar eventos
            }
        });
    }
}
