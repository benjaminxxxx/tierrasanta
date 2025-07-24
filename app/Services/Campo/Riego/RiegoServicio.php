<?php

namespace App\Services\Campo\Riego;

use App\Models\ConsolidadoRiego;
use App\Models\ReporteDiarioRiego;
use DB;
use Exception;

class RiegoServicio
{
    public static function eliminarRegistroRegador($riegoId)
    {
        DB::transaction(function () use ($riegoId) {
            $consolidado = ConsolidadoRiego::find($riegoId);

            if (!$consolidado) {
                throw new Exception("No se encontró el registro de riego con ID {$riegoId}.");
            }

            $fecha = $consolidado->fecha;
            $documento = $consolidado->regador_documento;

            ReporteDiarioRiego::where('documento', $documento)
                ->where('fecha', $fecha)
                ->delete();

            $consolidado->delete();
        });
    }
    public static function registrarRegadoresEnFecha($fecha, $listaRegadores)
    {
        foreach ($listaRegadores as $regador) {

            if (empty($regador['dni'])) {
                throw new Exception("Falta DNI en uno de los regadores.");
            }

            $nombre = $regador['nombre'] ?? null;
            static::crearConsolidado($fecha, $regador['dni'], $nombre);
        }
    }
    private static function crearConsolidado($fecha, $documento, $nombre_completo = '')
    {
        $existe = ConsolidadoRiego::where('regador_documento', $documento)
            ->where('fecha', $fecha)
            ->exists();

        if ($existe) {
            throw new Exception("Ya existe un consolidado para el regador con documento {$documento} en la fecha {$fecha}.");
        }

        ConsolidadoRiego::create([
            'regador_documento' => $documento,
            'regador_nombre' => $nombre_completo,
            'fecha' => $fecha,
            'hora_inicio' => null,
            'hora_fin' => null,
            'total_horas_riego' => 0,
            'total_horas_observaciones' => 0,
            'total_horas_acumuladas' => 0,
            'total_horas_jornal' => 0,
            'estado' => 'noconsolidado',
        ]);
    }

}
