<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContabilidadCostoDetalle extends Model
{
    use HasFactory;

    protected $table = 'contabilidad_costo_detalles';

    protected $fillable = [
        'registro_costo_id',
        'campo',
    ];

    public function registroCosto()
    {
        return $this->belongsTo(ContabilidadCostoRegistro::class, 'registro_costo_id');
    }
}
