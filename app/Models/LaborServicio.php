<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de sugerencias de labores de servicios externos (independiente de labores y labores_riego).
 */
class LaborServicio extends Model
{
    protected $table = 'labores_servicios';

    protected $fillable = ['nombre'];

    public static function registrarSiNoExiste(?string $nombre): void
    {
        $nombre = trim((string) $nombre);
        if ($nombre !== '') {
            self::firstOrCreate(['nombre' => $nombre]);
        }
    }

    public static function sugerencias(): array
    {
        return self::orderBy('nombre')->pluck('nombre')->toArray();
    }
}
