<?php

namespace App\Services\Campo\Riego;

use App\Models\ConsolidadoRiego;
use App\Models\ReporteDiarioRiego;
use App\Services\Campo\Gestion\CampoServicio;
use App\Support\FormatoHelper;
use DB;
use Exception;

class RiegoServicio
{

    public function procesarRegistroDiario(string $regador, string $fecha, array $data, string $nombreRegador): void
    {
        // 1. Extraer nombres de campos del array (asumiendo que el campo es el índice 0)
        $nombresCampos = collect($data)
            ->pluck(0)
            ->filter()
            ->unique()
            ->toArray();

        // 2. Validación masiva usando tu lógica existente
        $validacion = CampoServicio::validarCamposDesdeExcel($nombresCampos);

        if (!empty($validacion['invalidos'])) {
            throw new Exception("Los siguientes campos/lotes no son válidos: " . implode(', ', $validacion['invalidos']));
        }

        // Obtener el mapa de alias -> nombre_real
        $mapaCampos = $validacion['filtro'];

        DB::transaction(function () use ($regador, $fecha, $data, $nombreRegador, $mapaCampos) {

            ReporteDiarioRiego::where('documento', $regador)
                ->whereDate('fecha', $fecha)
                ->delete();

            foreach ($data as $row) {
                if (empty($row[0]))
                    continue;

                $aliasCampo = mb_strtolower(trim($row[0]));
                // Usamos el nombre real mapeado, si no existe (tsh/negro), usamos el original
                $nombreRealCampo = $mapaCampos[$aliasCampo] ?? $row[0];
                $hInicio = FormatoHelper::normalizarHora($row[1] ?? '00:00');
                $hFin    = FormatoHelper::normalizarHora($row[2] ?? '00:00');
                
                ReporteDiarioRiego::create([
                    'campo' => $nombreRealCampo,
                    'hora_inicio' => $hInicio,
                    'hora_fin' => $hFin,
                    //'total_horas' => isset($row[3]) ? $this->formatTime($row[3]) : '00:00',
                    'documento' => $regador,
                    'regador' => $nombreRegador,
                    'fecha' => $fecha,
                    'sh' => isset($row[6]) ? ($row[6] ? 1 : 0) : 0,
                    'tipo_labor' => isset($row[4]) && trim($row[4]) !== '' ? $row[4] : 'Riego',
                    'descripcion' => $row[5] ?? null,
                ]);
            }
        });
    }

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
