<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siembra extends Model
{
    use HasFactory;

    protected $table = 'siembras';

    protected $fillable = [
        'campo_nombre',
        'fecha_siembra',
        //'fecha_renovacion',

    ];

    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo_nombre', 'nombre');
    }

    public static function masProximaAntesDe($fecha, $campo)
    {
        if (!$fecha || !$campo) {
            return null;
        }
        return self::where('fecha_siembra', '<=', $fecha)
            ->where('campo_nombre', $campo)
            ->orderByDesc('fecha_siembra')
            ->first();
    }
    protected function casts(): array
    {
        return [
            'fecha_siembra' => 'date',
        ];
    }

    /**
     * Atributo Calculado: fecha_renovacion
     * * Busca la siguiente siembra del mismo campo y resta un día.
     */
    protected function fechaRenovacion(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Buscamos la fecha de la siembra inmediatamente posterior en este mismo campo
                $proximaSiembra = self::where('campo_nombre', $this->campo_nombre)
                    ->where('fecha_siembra', '>', $this->fecha_siembra)
                    ->orderBy('fecha_siembra', 'asc')
                    ->first();

                return $proximaSiembra
                    ? $proximaSiembra->fecha_siembra->subDay()
                    : null;
            }
        );
    }
    protected function numeroCampanias(): Attribute
    {
        return Attribute::make(
            get: function () {
                return CampoCampania::where('campo', $this->campo_nombre)
                    ->whereDate('fecha_inicio', '>=', $this->fecha_siembra)
                    ->when($this->fecha_renovacion, function ($q) {
                        $q->whereDate('fecha_inicio', '<=', $this->fecha_renovacion);
                    })
                    ->count();
            }
        );
    }

}
