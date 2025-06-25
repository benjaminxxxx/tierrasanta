<?php

namespace App\Support;

use App\Models\VentaCochinilla;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class FormatoHelper
{
    public static function generarCodigoGrupo(string $fechaReferencia): string
    {
        $base = Carbon::parse($fechaReferencia)->format('Ymd');
        $indice = 1;

        do {
            $codigo = "{$base}_{$indice}";
            $existe = VentaCochinilla::where('grupo_venta', $codigo)->exists();
            $indice++;
        } while ($existe);

        return $codigo;
    }

    /**
     * Convierte cualquier fecha textual en un formato válido de MySQL (Y-m-d o Y-m-d H:i:s).
     *
     * @param string|null $fechaTexto Fecha en formato textual (e.g. "12/4/2025", "2025-05-12", etc.)
     * @param bool $incluirHora Indica si debe incluir la hora (formato datetime)
     * @return string|null Fecha formateada en formato MySQL o null si no es válida
     */
    public static function parseFecha(?string $fechaTexto, bool $incluirHora = false): ?string
    {
        //Nueva Version
        if (is_null($fechaTexto) || trim($fechaTexto) === '') {
            return null;
        }

        $fechaTexto = trim($fechaTexto);

        // Mapeo de patrones regex a formatos Carbon
        $formatosRegex = [
            '/^\d{2}\/\d{2}\/\d{4}$/' => 'd/m/Y',
            '/^\d{2}-\d{2}-\d{4}$/' => 'd-m-Y',
            '/^\d{4}-\d{2}-\d{2}$/' => 'Y-m-d',
            '/^\d{4}\/\d{2}\/\d{2}$/' => 'Y/m/d',
            '/^\d{2}\.\d{2}\.\d{4}$/' => 'd.m.Y',
            '/^\d{4}\.\d{2}\.\d{2}$/' => 'Y.m.d',
            '/^\d{2} \w{3,9} \d{4}$/' => 'd F Y',  // o d M Y
        ];

        foreach ($formatosRegex as $regex => $formato) {
            if (preg_match($regex, $fechaTexto)) {
                try {
                    $fecha = \Carbon\Carbon::createFromFormat($formato, $fechaTexto);
                    return $incluirHora
                        ? $fecha->format('Y-m-d H:i:s')
                        : $fecha->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        // Ningún patrón coincide
        return null;
    }

    /*
    public static function parseFecha(?string $fechaTexto, bool $incluirHora = false): ?string
    {
        if (is_null($fechaTexto) || trim($fechaTexto) === '') {
            return null;
        }

        $formatos = [
            'd/m/Y',     // 16/04/2025
            'd-m-Y',     // 16-04-2025
            'Y-m-d',     // 2025-04-16
            'Y/m/d',     // 2025/04/16
            'd M Y',     // 16 Apr 2025
            'd F Y',     // 16 April 2025
            'd.m.Y',     // 16.04.2025
            'Y.m.d',     // 2025.04.16
        ];

        foreach ($formatos as $formato) {
            try {
                $fecha = \Carbon\Carbon::createFromFormat($formato, $fechaTexto);
                return $incluirHora
                    ? $fecha->format('Y-m-d H:i:s')
                    : $fecha->format('Y-m-d');
            } catch (\Exception $e) {
                // Continuar con el siguiente formato
            }
        }

        // Como fallback: intentar parseo libre con Carbon (último recurso)
        try {
            $fecha = \Carbon\Carbon::parse($fechaTexto);
            return $incluirHora
                ? $fecha->format('Y-m-d H:i:s')
                : $fecha->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }*/

}
