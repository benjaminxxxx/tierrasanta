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
        'evaluacion' => 'Agregada al registrar una evaluación',
        'correccion' => 'Corrección de datos (observaciones del ingeniero)',
    ];

    public const ORIGENES_CORTO = [
        'registro' => 'registrada a mano',
        'deteccion' => 'detectada por labores de siembra',
        'tarjeta' => 'agregada desde la tarjeta',
        'mapa' => 'agregada en el mapa de etapas',
        'horas' => 'aceptada al registrar horas',
        'evaluacion' => 'agregada al registrar una evaluación',
        'correccion' => 'corregida por observaciones del ingeniero',
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

    /**
     * Plantación de una campaña (Ing. Lucio, 24/11/2026): la que se plantó dentro de la campaña antes de infestarla
     * (se mató el campo y se volvió a plantar); si no hubo, la última plantación anterior a su inicio (la campaña
     * pasada). La que se planta el mismo día del cierre ya es de la campaña siguiente.
     */
    public static function deCampania(CampoCampania $c): ?self
    {
        if (!$c->fecha_inicio) {
            return null;
        }
        $inicio = \Illuminate\Support\Carbon::parse($c->fecha_inicio)->toDateString();
        $fin = $c->fecha_fin ? \Illuminate\Support\Carbon::parse($c->fecha_fin)->toDateString() : null;
        $infestada = collect([
            $c->infestacion_fecha,
            $c->id ? \Illuminate\Support\Facades\DB::table('cochinilla_infestaciones')->where('campo_campania_id', $c->id)->min('fecha') : null,
        ])->filter()->map(fn($f) => \Illuminate\Support\Carbon::parse($f)->toDateString())->min();

        return self::plantaciones()->where('campo_nombre', $c->campo)->whereDate('fecha_siembra', '>=', $inicio)
            ->when($fin, fn($q) => $q->whereDate('fecha_siembra', '<', $fin))
            ->when($infestada, fn($q) => $q->whereDate('fecha_siembra', '<', $infestada))
            ->orderBy('fecha_siembra')->orderBy('id')->first()
            ?? self::plantaciones()->where('campo_nombre', $c->campo)->whereDate('fecha_siembra', '<=', $inicio)
                ->orderByDesc('fecha_siembra')->orderBy('id')->first();
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
                // Campañas cuya plantación es esta (la misma regla que la fecha de siembra de la campaña)
                $fecha = $this->fecha_siembra?->toDateString();
                return CampoCampania::where('campo', $this->campo_nombre)->get()
                    ->filter(fn($c) => self::deCampania($c)?->fecha_siembra?->toDateString() === $fecha)
                    ->count();
            }
        );
    }
}
