<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Unidad de producción (KG, LAVADOR, CAJA…). Las labores guardan su código en labores.unidades. */
class ProduccionUnidad extends Model
{
    protected $table = 'produccion_unidades';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['codigo', 'nombre', 'decimales', 'activo'];

    protected $casts = ['activo' => 'boolean', 'decimales' => 'integer'];
}
