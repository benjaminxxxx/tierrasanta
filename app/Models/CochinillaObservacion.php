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
        'es_vendible', // false = va a infestación y vuelve como ingreso de infestadores
    ];

    protected $casts = ['es_cosecha_mama' => 'boolean', 'es_vendible' => 'boolean'];

    private static ?array $vendibles = null;

    /** codigo => si es vendible (cacheado por petición). */
    public static function vendibles(): array
    {
        return self::$vendibles ??= self::pluck('es_vendible', 'codigo')->map(fn($v) => (bool) $v)->all();
    }

    public function ingresos()
    {
        return $this->hasMany(CochinillaIngreso::class, 'observacion', 'codigo');
    }

    public function detalles()
    {
        return $this->hasMany(CochinillaIngresoDetalle::class, 'observacion', 'codigo');
    }
    // Dentro de la clase CochinillaObservacion
    public function scopeCosechasMama($query)
    {
        return $query->where('es_cosecha_mama', true);
    }

}
