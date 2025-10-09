<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labores extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "labores";

    protected $fillable = [
        'nombre_labor',
        'codigo',
        'estandar_produccion',
        'unidades',
        'tramos_bonificacion',
        'codigo_mano_obra',
        'creado_por',
        'actualizado_por',
        'eliminado_por',
    ];
    public function manoObra()
    {
        return $this->belongsTo(ManoObra::class, 'codigo_mano_obra', 'codigo');
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
