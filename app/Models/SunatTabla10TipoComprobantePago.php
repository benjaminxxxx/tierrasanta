<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatTabla10TipoComprobantePago extends Model
{
    protected $table = 'sunat_tabla10_tipo_comprobantes_pago';

    protected $primaryKey = 'codigo'; // Definimos 'codigo' como la clave primaria

    public $incrementing = false; // No incrementará automáticamente el campo código, ya que es un valor fijo.

    protected $fillable = [
        'codigo', 'descripcion'
    ];

    public $timestamps = false;
}
