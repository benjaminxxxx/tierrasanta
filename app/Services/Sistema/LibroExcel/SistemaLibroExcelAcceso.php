<?php

namespace App\Services\Sistema\LibroExcel;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Claves de acceso de los libros Excel con macro. Cada descarga emite una clave personal para ese usuario y libro:
 * - El sistema guarda solo su huella (Sanctum la guarda con SHA-256); el texto plano va únicamente en el archivo.
 * - Sirve solo para ese libro (habilidad "libro:{clave}") y vence en DIAS_VIGENCIA días.
 * - Descargar de nuevo anula la clave anterior de ese libro: solo el último archivo descargado funciona.
 * Cada petición manda además el correo del usuario: si no coincide con el dueño de la clave, se rechaza.
 */
class SistemaLibroExcelAcceso
{
    public const DIAS_VIGENCIA = 7;

    /** @return array{clave: string, vence: Carbon} */
    public function emitir(User $usuario, string $libro): array
    {
        $nombre = self::nombreClave($libro);
        $usuario->tokens()->where('name', $nombre)->delete();
        $vence = now()->addDays(self::DIAS_VIGENCIA);
        $token = $usuario->createToken($nombre, ["libro:{$libro}"], $vence);
        return ['clave' => $token->plainTextToken, 'vence' => $vence];
    }

    public static function nombreClave(string $libro): string
    {
        return "excel:{$libro}";
    }
}
