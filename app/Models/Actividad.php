<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    use HasFactory;
    protected $table = 'actividades';

    protected $fillable = [
        'fecha',
        'campo',
        'labor_id',
        'nombre_labor',
        'codigo_labor',
        'recojos',
        //'tramos_bonificacion',
        //'tramos_bonificacion_destajo',
        //'estandar_produccion',
        'unidades',
        'creado_por',
        'actualizado_por',
    ];
    public function labores()
    {
        return $this->belongsTo(Labores::class, 'labor_id');
    }
    public function metodos(): HasMany
    {
        return $this->hasMany(ActividadMetodo::class, 'actividad_id')->orderBy('orden');
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
