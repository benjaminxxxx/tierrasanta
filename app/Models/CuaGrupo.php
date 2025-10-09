<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
//antes GruposCuadrilla
class CuaGrupo extends Model
{
    use SoftDeletes;
    protected $table = 'cuad_grupos';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'color',
        'nombre',
        'costo_dia_sugerido',
        'modalidad_pago',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];
    public function cuadrilleros()
    {
        return $this->hasMany(Cuadrillero::class, 'codigo_grupo', 'codigo');
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
