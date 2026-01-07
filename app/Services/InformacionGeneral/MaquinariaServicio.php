<?php

namespace App\Services\InformacionGeneral;

use App\Models\Maquinaria;
use Exception;

class MaquinariaServicio
{
    public static function validarMaquinariasDesdeExcel(array $nombresExcel): array
    {
        $espaciosRaros = ["\u{00A0}", "\u{200B}", "\u{FEFF}"];

        // Normalizar nombres desde Excel
        $nombresLimpios = collect($nombresExcel)
            ->map(fn($v) => mb_strtolower(trim(str_replace($espaciosRaros, '', $v ?? ''))))
            ->filter()
            ->unique()
            ->values();

        // Obtener todas las maquinarias una sola vez
        $maquinarias = Maquinaria::query()
            ->select('id', 'nombre', 'alias_blanco')
            ->get();

        // Mapa normalizado: nombre / alias_blanco → id
        $mapaMaquinarias = [];

        foreach ($maquinarias as $m) {
            if ($m->nombre) {
                $mapaMaquinarias[mb_strtolower(trim($m->nombre))] = $m->id;
            }

            if ($m->alias_blanco) {
                $mapaMaquinarias[mb_strtolower(trim($m->alias_blanco))] = $m->id;
            }
        }

        // Detectar inválidos
        $invalidos = $nombresLimpios
            ->reject(fn($nombre) => array_key_exists($nombre, $mapaMaquinarias))
            ->values()
            ->all();

        if (!empty($invalidos)) {
            throw new Exception(
                "Las siguientes maquinarias no existen en el sistema: " .
                implode(', ', $invalidos) .
                ". Deben registrarse antes de importar el Kardex."
            );
        }

        // Filtro final: nombre_excel → maquinaria_id
        $filtro = $nombresLimpios
            ->mapWithKeys(fn($nombre) => [$nombre => $mapaMaquinarias[$nombre]])
            ->all();

        return $filtro;
    }

}
