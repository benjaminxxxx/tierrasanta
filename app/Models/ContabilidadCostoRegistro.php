<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContabilidadCostoRegistro extends Model
{
    use HasFactory;

    protected $table = 'contabilidad_costo_registros';

    protected $fillable = [
        'nombre_costo_id',
        'fecha',
        'valor',
    ];

    public function tipoCosto()
    {
        return $this->belongsTo(ContabilidadCostoTipo::class, 'nombre_costo_id');
    }

    public function detalles()
    {
        return $this->hasMany(ContabilidadCostoDetalle::class, 'registro_costo_id');
    }
}
