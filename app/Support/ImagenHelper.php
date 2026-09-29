<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Guarda imágenes subidas ajustadas: sin deformar (se conserva la proporción ancho/alto),
 * con el lado mayor limitado, la orientación de la cámara corregida y en JPG.
 */
class ImagenHelper
{
    public static function guardarAjustada(UploadedFile $archivo, string $carpeta, int $ladoMaximo = 1024, int $calidad = 82): string
    {
        $ruta = $archivo->getRealPath();
        $tipo = @exif_imagetype($ruta);

        $imagen = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
            default => false,
        };
        if (!$imagen) {
            throw new RuntimeException('La foto debe ser JPG, PNG o WEBP.');
        }

        // Fotos de celular: la cámara guarda la rotación en EXIF en vez de rotar los píxeles
        if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientacion = @exif_read_data($ruta)['Orientation'] ?? 1;
            $imagen = match ((int) $orientacion) {
                3 => imagerotate($imagen, 180, 0),
                6 => imagerotate($imagen, -90, 0),
                8 => imagerotate($imagen, 90, 0),
                default => $imagen,
            };
        }

        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $escala = min(1, $ladoMaximo / max($ancho, $alto)); // nunca se agranda
        $nuevoAncho = (int) round($ancho * $escala);
        $nuevoAlto = (int) round($alto * $escala);

        $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        // Fondo blanco para PNG/WEBP con transparencia (el JPG no la soporta)
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

        $nombre = trim($carpeta, '/') . '/' . Str::uuid() . '.jpg';
        Storage::disk('public')->makeDirectory(trim($carpeta, '/'));
        imagejpeg($lienzo, Storage::disk('public')->path($nombre), $calidad);

        imagedestroy($imagen);
        imagedestroy($lienzo);

        return $nombre;
    }
}
