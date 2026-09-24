<?php
// app/Support/ColorContraste.php
namespace App\Support;

class ColorContraste
{
    // Fórmula estándar de luminancia relativa (WCAG simplificada) para decidir
    // si el texto debe ir blanco o negro sobre un color de fondo dado.
    public static function textoPara(string $hexColor): string
    {
        $hex = ltrim($hexColor, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '#000000';
        }

        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $luminancia = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminancia > 0.55 ? '#000000' : '#FFFFFF';
    }
}