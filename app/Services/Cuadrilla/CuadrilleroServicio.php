<?php

namespace App\Services\Cuadrilla;

use App\Models\CuaAsistenciaSemanal;
use App\Models\CuadrilleroActividad;

class CuadrilleroServicio
{
    public static function buscarSemana(string $fecha): CuaAsistenciaSemanal
    {
        $semana = CuaAsistenciaSemanal::whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->firstOrFail();

        if (!$semana) {
            throw new \Exception("No hay una semana para esta fecha {$fecha}");
        }

        return $semana;
    }
    /**
     * Devuelve los cuadrilleros con asistencia en una fecha dada
     */
    public static function obtenerCuadrillerosEnFecha(string $fecha)
    {
        $semana = self::buscarSemana($fecha);
        $grupos = $semana->grupos;

        if (!$grupos) {
            throw new \Exception("No hay ningún grupo en el registro semanal {$semana->id}");
        }

        $lista = [];

        foreach ($grupos as $grupo) {
            $cuadrilleros = $grupo->cuadrillerosEnAsistencia;
            if ($cuadrilleros) {
                foreach ($cuadrilleros as $cuadrillero) {
                    $data = $cuadrillero->cuadrillero;
                    $lista[] = [
                        'cua_asi_sem_cua_id' => $cuadrillero->id,
                        'id' => $data->id,
                        'grupo' => $grupo->id,
                        'grupo_nombre' => $grupo->grupo->nombre,
                        'tipo' => 'cuadrilla',
                        'dni' => $data->dni,
                        'nombres' => $data->nombres,
                    ];
                }
            }
        }

        return collect($lista)->sortBy(['grupo', 'nombres'])->values();
    }
    public static function obtenerTrabajadoresXDia($fecha, $actividadId = null)
    {
        $cuadrillerosEnFecha = self::obtenerCuadrillerosEnFecha($fecha);
        $cuadrillerosAgregados = $cuadrillerosEnFecha->toArray();

        foreach ($cuadrillerosAgregados as $indice => $cuadrilleroAgregado) {
            $cua_asi_sem_cua_id = $cuadrilleroAgregado['cua_asi_sem_cua_id'];

            // valores por defecto
            $cuadrillerosAgregados[$indice]['bono'] = '-';
            $cuadrillerosAgregados[$indice]['horas'] = 0;
            $cuadrillerosAgregados[$indice]['costo_diario'] = 0;
            $cuadrillerosAgregados[$indice]['total'] = 0;

            // Solo si actividadId está presente, buscamos los datos
            if ($actividadId) {
                $actividad = CuadrilleroActividad::where('actividad_id', $actividadId)
                    ->where('cua_asi_sem_cua_id', $cua_asi_sem_cua_id)
                    ->first();

                if ($actividad) {
                    // asignar datos básicos
                    $cuadrillerosAgregados[$indice]['bono'] = $actividad->total_bono ?? 0;
                    $cuadrillerosAgregados[$indice]['horas'] = $actividad->total_horas ?? 0;
                    $cuadrillerosAgregados[$indice]['costo_diario'] = $actividad->total_costo ?? 0;

                    // calcular total
                    $cuadrillerosAgregados[$indice]['total'] =
                        ($actividad->total_costo ?? 0) + ($actividad->total_bono ?? 0);

                    // ahora expandir cantidades
                    $cantidades = $actividad->cantidades ?? [];
                    if (is_string($cantidades)) {
                        $cantidades = json_decode($cantidades, true) ?? [];
                    }

                    foreach ($cantidades as $i => $cantidad) {
                        $key = 'cantidad_' . ($i + 1);
                        $cuadrillerosAgregados[$indice][$key] = $cantidad;
                    }
                }
            }
        }

        return $cuadrillerosAgregados;
    }

}
