<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de siembras del campo. Es la misma fuente que las siembras del mapa de etapas (campania_sectores.siembra_id):
 *  - tipo "siembra": plantación del campo (renovación); define la fecha de renovación y el n° de campañas;
 *  - tipo "relleno": parte del campo sembrada en otra fecha dentro de una campaña (ej. a 2 m meses después).
 * origen/motivo/detalle dicen por qué existe (a mano, detectada por labores, desde la tarjeta, el mapa o las horas).
 */
class Siembra extends Model
{
    use HasFactory;

    protected $table = 'siembras';

    public const TIPOS = ['siembra' => 'Siembra', 'relleno' => 'Relleno'];

    public const ORIGENES = [
        'registro' => 'Registrada a mano en Campo → Siembras',
        'deteccion' => 'Detectada por las labores de siembra registradas',
        'tarjeta' => 'Agregada desde la tarjeta de la campaña',
        'mapa' => 'Agregada en el mapa de etapas',
        'horas' => 'Aceptada al registrar horas',
    ];

    public const ORIGENES_CORTO = [
        'registro' => 'registrada a mano',
        'deteccion' => 'detectada por labores de siembra',
        'tarjeta' => 'agregada desde la tarjeta',
        'mapa' => 'agregada en el mapa de etapas',
        'horas' => 'aceptada al registrar horas',
    ];

    protected $fillable = [
        'campo_nombre',
        'campo_campania_id',
        'fecha_siembra',
        'tipo',
        'origen',
        'motivo',
        'detalle',
        'creado_por',
    ];

    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo_nombre', 'nombre');
    }

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }

    /** Siembras del mapa de etapas que usan este registro */
    public function sectores()
    {
        return $this->hasMany(CampaniaSector::class, 'siembra_id');
    }

    /** Solo plantaciones (sin rellenos): renovación, n° de campañas, fecha de siembra de una campaña. */
    public function scopePlantaciones(Builder $q): Builder
    {
        return $q->where('tipo', 'siembra');
    }

    public static function masProximaAntesDe($fecha, $campo)
    {
        if (!$fecha || !$campo) {
            return null;
        }
        return self::plantaciones()->where('fecha_siembra', '<=', $fecha)
            ->where('campo_nombre', $campo)
            ->orderByDesc('fecha_siembra')
            ->first();
    }

    protected function casts(): array
    {
        return [
            'fecha_siembra' => 'date',
            'detalle' => 'array',
        ];
    }

    public function getOrigenTextoAttribute(): string
    {
        return self::ORIGENES[$this->origen] ?? (string) $this->origen;
    }

    /**
     * Atributo Calculado: fecha_renovacion
     * Busca la siguiente plantación del mismo campo y resta un día (un relleno no renueva el campo).
     */
    protected function fechaRenovacion(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->tipo === 'relleno') {
                    return null;
                }
                $proximaSiembra = self::plantaciones()->where('campo_nombre', $this->campo_nombre)
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
                if ($this->tipo === 'relleno') {
                    return null;
                }
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
