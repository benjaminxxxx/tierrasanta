<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siembra extends Model
{
    use HasFactory;

    protected $table = 'siembras';

    protected $fillable = [
        'campo_nombre',
        'fecha_siembra',
        'fecha_renovacion',

    ];

    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo_nombre', 'nombre');
    }

    public static function masProximaAntesDe($fecha, $campo)
    {
        if(!$fecha || !$campo){
            return null;
        }
        return self::where('fecha_siembra', '<=', $fecha)
            ->where('campo_nombre', $campo)
            ->orderByDesc('fecha_siembra')
            ->first();
    }
}
