<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametroTemporal extends Model
{
    protected $table = 'parametros_temporales';

    protected $fillable = [
        'tipo',
        'fecha',
        'valor',
        'creado_por',
        'actualizado_por'
    ];

    protected $casts = [
        'fecha' => 'date'
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    // Obtener valor para una fecha específica
    public static function obtener(string $tipo, string $fecha, mixed $default = null): mixed
    {
        return Cache::remember("param_{$tipo}_{$fecha}", 60, function () use ($tipo, $fecha, $default) {
            return static::where('tipo', $tipo)
                ->whereDate('fecha', $fecha)
                ->value('valor') ?? $default;
        });
    }

    // Guardar o actualizar (upsert)
    public static function guardar(string $tipo, string $fecha, mixed $valor, int $userId): self
    {
        Cache::forget("param_{$tipo}_{$fecha}");

        return static::updateOrCreate(
            ['tipo' => $tipo, 'fecha' => $fecha],
            ['valor' => $valor, 'creado_por' => $userId, 'actualizado_por' => $userId]
        );
    }

    public static function limiteMinutosDiarios(string $fecha): int
    {
        $horas = (int) static::obtener('limite_horas_riego', $fecha, 8);
        return $horas * 60;
    }
}