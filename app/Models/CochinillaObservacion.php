<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CochinillaObservacion extends Model
{
    protected $table = "cochinilla_observaciones";
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'es_cosecha_mama',
    ];

    public function ingresos()
    {
        return $this->hasMany(CochinillaIngreso::class, 'observacion', 'codigo');
    }

    public function detalles()
    {
        return $this->hasMany(CochinillaIngresoDetalle::class, 'observacion', 'codigo');
    }
}
