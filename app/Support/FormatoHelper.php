<?php

namespace App\Support;

use App\Models\VentaCochinilla;
use Illuminate\Support\Carbon;

class FormatoHelper
{
    /**
     * Convierte texto de número (con comas/puntos como separadores) a float.
     * Soporta: "1,334.50" | "1.334,50" | "1334.50" | "1334,50"
     */
    public static function parseNumero(?string $valor): ?float
    {
        if (is_null($valor) || trim($valor) === '') {
            return null;
        }

        $valor = trim($valor);

        // Si tiene coma Y punto, el último separador es el decimal
        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $ultimaComa = strrpos($valor, ',');
            $ultimoPunto = strrpos($valor, '.');

            if ($ultimaComa > $ultimoPunto) {
                // formato europeo: 1.334,50
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            } else {
                // formato anglosajón: 1,334.50
                $valor = str_replace(',', '', $valor);
            }
        } elseif (str_contains($valor, ',')) {
            // Solo coma → decimal europeo: "1334,50"
            $valor = str_replace(',', '.', $valor);
        }
        // Solo punto → ya es válido: "1334.50"

        return is_numeric($valor) ? (float) $valor : null;
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
            '/^\d{1,2}\/\d{1,2}\/\d{4}$/' => 'd/m/Y',
            '/^\d{1,2}-\d{1,2}-\d{4}$/' => 'd-m-Y',
            '/^\d{4}-\d{2}-\d{2}$/' => 'Y-m-d',
            '/^\d{4}\/\d{2}\/\d{2}$/' => 'Y/m/d',
            '/^\d{1,2}\.\d{1,2}\.\d{4}$/' => 'd.m.Y',
            '/^\d{4}\.\d{2}\.\d{2}$/' => 'Y.m.d',
            '/^\d{1,2} \w{3,9} \d{4}$/' => 'd F Y',
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
    /**
     * Normaliza formatos como "14.30", "14:30" o "14" a "14:30:00"
     */
    public static function normalizarHora(?string $hora): string
    {
        if (empty($hora))
            return '00:00:00';

        // Reemplazar punto por dos puntos para estandarizar
        $hora = str_replace('.', ':', trim($hora));

        // Si solo enviaron el número de hora (ej: "14"), completar con minutos
        if (!str_contains($hora, ':')) {
            $hora .= ':00';
        }

        try {
            // Forzamos el parseo y devolvemos formato 24h
            return Carbon::parse($hora)->format('H:i:s');
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }
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


    public static function parseNumeroDesdeFuenteExterna(?string $texto): ?float
    {
        if (is_null($texto)) {
            return null;
        }

        // Elimina espacios
        $texto = trim($texto);

        // Si está vacío, devolver null
        if ($texto === '') {
            return null;
        }

        // Quitar separadores de miles (comas)
        $texto = str_replace(',', '', $texto);

        // Convertir a float
        return is_numeric($texto) ? (float) $texto : null;
    }

}
