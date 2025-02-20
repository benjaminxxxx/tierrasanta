<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContabilidadCostoTipo extends Model
{
    use HasFactory;

    protected $table = 'contabilidad_costo_tipos';

    protected $fillable = [
        'nombre_costo',
        'tipo_costo',
    ];

    public function registros()
    {
        return $this->hasMany(ContabilidadCostoRegistro::class, 'nombre_costo_id');
    }
}
