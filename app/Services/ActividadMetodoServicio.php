<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\ActividadMetodo;

class ActividadMetodoServicio
{
    public static function sincronizarMetodos(Actividad $actividad, array $metodos): array
    {
        $mapaMetodos = []; // ['Método x Jornal #1' => 5, 'Método x Sobreestandar #2' => 6]
        $idsEnviados = [];

        foreach ($metodos as $orden => $datosMetodo) {
            $titulo = self::generarTitulo($datosMetodo, $orden);

            $metodo = $actividad->metodos()->updateOrCreate(
                ['orden' => $orden + 1],
                [
                    'titulo' => $titulo,
                    'estandar' => $datosMetodo['estandar'] ?: null,
                    'orden' => $orden + 1,
                ]
            );

            $idsEnviados[] = $metodo->id;
            $mapaMetodos[$titulo] = $metodo->id; // ← acumular mapa

            self::sincronizarTramos($metodo, $datosMetodo['tramos']);
        }

        // Eliminar huérfanos
        $actividad->metodos()
            ->whereNotIn('id', $idsEnviados)
            ->each(function (ActividadMetodo $metodo) {
                $metodo->tramos()->delete();
                $metodo->delete();
            });

        return $mapaMetodos;
    }

    private static function sincronizarTramos(ActividadMetodo $metodo, array $tramos): void
    {
        $idsEnviados = [];

        foreach ($tramos as $orden => $datoTramo) {
            // Ignorar tramos completamente vacíos
            if ($datoTramo['hasta'] === '' && $datoTramo['monto'] === '') {
                continue;
            }

            $tramo = $metodo->tramos()->updateOrCreate(
                ['orden' => $orden + 1],
                [
                    'hasta' => $datoTramo['hasta'] !== '' ? $datoTramo['hasta'] : null,
                    'monto' => $datoTramo['monto'],
                    'orden' => $orden + 1,
                ]
            );

            $idsEnviados[] = $tramo->id;
        }

        // Eliminar tramos que ya no existen
        $metodo->tramos()
            ->whereNotIn('id', $idsEnviados)
            ->delete();
    }

    private static function generarTitulo(array $datosMetodo, int $indice): string
    {
        $numero = $indice + 1;
        return $datosMetodo['estandar']
            ? "Método x Sobreestandar #{$numero}"
            : "Método x Jornal #{$numero}";
    }
}