<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatTabla5TipoExistencia extends Model
{
    protected $table = 'sunat_tabla5_tipo_existencias';

    protected $primaryKey = 'codigo'; // Definimos 'codigo' como la clave primaria

    public $incrementing = false; // No incrementará automáticamente el campo código, ya que es un valor fijo.

    protected $fillable = [
        'codigo', 'descripcion'
    ];

    public $timestamps = false;
}
