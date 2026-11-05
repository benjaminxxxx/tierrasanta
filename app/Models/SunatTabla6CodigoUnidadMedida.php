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

    /** Código para el kardex: las unidades propias (no numéricas, p. ej. CAM = camión) van como 99 (otros, especificar). */
    public function getCodigoSunatAttribute(): string
    {
        return ctype_digit((string) $this->codigo) ? $this->codigo : '99';
    }
}
