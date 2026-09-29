<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de sugerencias de nombres de servicio. Los servicios guardan el texto, no el id.
 */
class ServicioNombre extends Model
{
    protected $table = 'servicios_nombres';

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
