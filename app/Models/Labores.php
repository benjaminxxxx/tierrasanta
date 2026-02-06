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
        'codigo_mano_obra',
        'codigo',
        'estandar_produccion',
        'unidades',
        'tramos_bonificacion',
        'creado_por',
        'actualizado_por',
        'eliminado_por'
    ];
    public function manoObra()
    {
        return $this->belongsTo(ManoObra::class, 'codigo_mano_obra', 'codigo');
    }
    protected $casts = [
        'tramos_bonificacion' => 'array',
    ];
}
