<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatTabla6CodigoUnidadMedida extends Model
{
    protected $table = 'sunat_tabla6_codigo_unidad_medida';

    protected $primaryKey = 'codigo'; // Definimos 'codigo' como la clave primaria

    public $incrementing = false; // No incrementará automáticamente el campo código, ya que es un valor fijo.

    protected $fillable = [
        'codigo', 'descripcion','alias'
    ];

    public $timestamps = false;
}
