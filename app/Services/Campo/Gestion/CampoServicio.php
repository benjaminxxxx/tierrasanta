<?php

namespace App\Services\Campo\Gestion;

use App\Models\Campo;

class CampoServicio
{
    /**
     * Retorna un array: alias (en minúsculas) => nombre_real del campo
     * Incluye también el nombre como clave para permitir validación directa.
     *
     * @return array
     */
    public static function obtenerMapaCamposNormalizados(): array
    {
        $mapa = [];

        $campos = Campo::all();

        foreach ($campos as $campo) {
            // Agrega el nombre como clave y valor directo
            $mapa[mb_strtolower($campo->nombre)] = $campo->nombre;

            // Agrega todos los alias en minúscula
            if (!empty($campo->alias)) {
                $aliasArray = array_map('trim', explode(',', $campo->alias));
                foreach ($aliasArray as $alias) {
                    $mapa[mb_strtolower($alias)] = $campo->nombre;
                }
            }
        }

        return $mapa;
    }
    /**
     * Normaliza y valida los nombres de campos recibidos desde Excel.
     *
     * @param array $camposExcel Lista de campos extraídos del archivo Excel (pueden contener espacios raros, alias, etc.)
     * @return array Lista de campos inválidos (que no existen ni como nombre ni alias, excepto "tsh", "negro", o vacíos)
     */
    public static function validarCamposDesdeExcel(array $camposExcel): array
    {
        // Mapa completo del sistema con alias como clave
        $mapaCampos = self::obtenerMapaCamposNormalizados();

        // Espacios invisibles que Excel puede introducir
        $espaciosRaros = ["\u{00A0}", "\u{200B}", "\u{FEFF}"];

        // Campos válidos adicionales permitidos
        $permitidosExtras = ['tsh', 'negro'];

        // Campos normalizados desde el Excel
        $camposLimpios = collect($camposExcel)
            ->map(fn($v) => mb_strtolower(trim(str_replace($espaciosRaros, '', $v ?? ''))))
            ->filter() // Eliminar nulos y vacíos
            ->unique()
            ->values();

        // Validar los campos
        $camposInvalidos = $camposLimpios->filter(
            fn($campo) =>
            !array_key_exists($campo, $mapaCampos) && !in_array($campo, $permitidosExtras)
        )->values()->all();

        // Solo devolver los campos válidos que sí tienen equivalencia
        $filtroCampos = $camposLimpios
            ->reject(fn($campo) => in_array($campo, $camposInvalidos))
            ->mapWithKeys(function ($alias) use ($mapaCampos) {
                return [$alias => $mapaCampos[$alias] ?? $alias]; // tsh y negro se mantienen
            })->all();

        return [
            'invalidos' => $camposInvalidos,
            'filtro' => $filtroCampos, // para mapear directamente en el upsert
        ];
    }

}
