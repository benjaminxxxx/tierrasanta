<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'tramos_bonificacion',
        'estandar_produccion',
        'unidades',
        'creado_por',
        'actualizado_por',
    ];
    public function labores()
    {
        return $this->belongsTo(Labores::class, 'labor_id');
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
