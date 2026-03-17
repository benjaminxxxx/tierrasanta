<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametroMensual extends Model
{
    use HasFactory;

    protected $table = 'parametros_mensuales';

    protected $fillable = [
        'mes',
        'anio',
        'clave',
        'valor',
        'valor_texto',
        'valor_flag',
        'observacion',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'valor' => 'float',
        'valor_flag' => 'boolean',
        'mes' => 'integer',
        'anio' => 'integer',
    ];
    public static function obtenerMes(int $mes, int $anio, array $claves = []): \Illuminate\Support\Collection
    {
        return self::where(compact('mes', 'anio'))
            ->when($claves, fn($q) => $q->whereIn('clave', $claves))
            ->get()
            ->keyBy('clave');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor: valor_final
    |--------------------------------------------------------------------------
    | Devuelve el valor real independientemente del tipo
    */

    public function getValorFinalAttribute()
    {
        if (!is_null($this->valor)) {
            return $this->valor;
        }

        if (!is_null($this->valor_flag)) {
            return $this->valor_flag;
        }

        return $this->valor_texto;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePeriodo($query, $mes, $anio)
    {
        return $query->where('mes', $mes)
            ->where('anio', $anio);
    }

    public function scopeClave($query, $clave)
    {
        return $query->where('clave', $clave);
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper estático
    |--------------------------------------------------------------------------
    | Obtiene un parámetro fácilmente
    */

    public static function obtener($clave, $mes, $anio, $default = null)
    {
        $param = static::where('clave', $clave)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        return $param?->valor_final ?? $default;
    }
    // ─── Helpers de escritura ─────────────────────────────────────────

    public static function establecer(
        int $mes,
        int $anio,
        string $clave,
        mixed $valor = null,
        ?string $valorTexto = null,
        ?bool $valorFlag = null,
        ?string $observacion = null
    ): self {
        return self::updateOrCreate(
            compact('mes', 'anio', 'clave'),
            array_filter([
                'valor' => $valor,
                'valor_texto' => $valorTexto,
                'valor_flag' => $valorFlag,
                'observacion' => $observacion,
            ], fn($v) => $v !== null)
        );
    }

    public static function establecerFlag(int $mes, int $anio, string $clave, bool $flag, string $observacion = ''): self
    {
        return self::updateOrCreate(
            compact('mes', 'anio', 'clave'),
            ['valor_flag' => $flag, 'observacion' => $observacion]
        );
    }

    public static function establecerMonto(int $mes, int $anio, string $clave, float $monto, ?string $archivo = null): self
    {
        return self::updateOrCreate(
            compact('mes', 'anio', 'clave'),
            ['valor' => $monto, 'valor_texto' => $archivo]
        );
    }
}
