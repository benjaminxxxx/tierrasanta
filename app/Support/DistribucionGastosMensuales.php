<?php

namespace App\Support;

use DateTime;

class DistribucionGastosMensuales
{
    public static function calcular(int $anio, int $mes, array $gastosMensuales, array $campanias): array
    {
        $inicioMes = new DateTime("{$anio}-{$mes}-01");
        $finMes = (clone $inicioMes)->modify('last day of this month');
        $diasMes = (int) $finMes->format('j');

        // 1. Preparar datos y calcular pesos
        $datosProcesados = self::prepararPesos($campanias, $inicioMes, $finMes);
        $pesoTotal = array_sum(array_column($datosProcesados, 'dias_activos'));

        // 2. Distribución
        $resultado = [];
        $acumuladosPorGasto = array_fill_keys(array_keys($gastosMensuales), 0.0);
        
        // Identificamos la última campaña QUE TENGA PESO para el ajuste de redondeo
        $ultimaCampaniaConPeso = self::obtenerUltimoIdConPeso($datosProcesados);

        foreach ($datosProcesados as $campania) {
            $tienePeso = $campania['dias_activos'] > 0 && $pesoTotal > 0;
            $esUltimaParaRedondeo = ($campania['campania_id'] === $ultimaCampaniaConPeso);
            
            $porcentaje = $tienePeso ? ($campania['dias_activos'] / $pesoTotal) : 0;

            $fila = [
                'campania_id'  => $campania['campania_id'],
                'nombre_campania' => $campania['nombre_campania'],
                'fecha_inicio' => $campania['fecha_inicio'],
                'fecha_fin' => $campania['fecha_fin'],
                'anio'         => $anio,
                'mes'          => $mes,
                'dias_mes'     => $diasMes,
                'dias_activos' => $campania['dias_activos'],
                'porcentaje'   => round($porcentaje, 6),
            ];

            foreach ($gastosMensuales as $tipo => $montoTotal) {
                $montoFila = 0.00;

                if ($tienePeso) {
                    if ($esUltimaParaRedondeo) {
                        $montoFila = round($montoTotal - $acumuladosPorGasto[$tipo], 2);
                    } else {
                        $montoFila = round($montoTotal * $porcentaje, 2);
                        $acumuladosPorGasto[$tipo] += $montoFila;
                    }
                }

                $fila["monto_{$tipo}"] = max(0, $montoFila);
            }

            $resultado[] = $fila;
        }

        return $resultado;
    }

    private static function prepararPesos(array $campanias, DateTime $inicioMes, DateTime $finMes): array
    {
        $preparados = [];
        foreach ($campanias as $campania) {
            $diasActivos = 0;

            if (!empty($campania['fecha_inicio'])) {
                $inicioCampania = new DateTime($campania['fecha_inicio']);
                // Si fecha_fin es null, usamos el fin del mes como fecha límite
                $finCampania = !empty($campania['fecha_fin']) 
                    ? new DateTime($campania['fecha_fin']) 
                    : clone $finMes;

                $inicioReal = max($inicioCampania, $inicioMes);
                $finReal = min($finCampania, $finMes);

                if ($inicioReal <= $finReal) {
                    $diasActivos = $inicioReal->diff($finReal)->days + 1;
                }
            }

            $preparados[] = [
                'campania_id'  => $campania['campania_id'],
                'nombre_campania' => $campania['nombre_campania'],
                'fecha_inicio' => $campania['fecha_inicio'],
                'fecha_fin' => $campania['fecha_fin'],
                'dias_activos' => $diasActivos,
            ];
        }
        return $preparados;
    }

    private static function obtenerUltimoIdConPeso(array $datos): ?int
    {
        $filtrados = array_filter($datos, fn($d) => $d['dias_activos'] > 0);
        if (empty($filtrados)) return null;
        return end($filtrados)['campania_id'];
    }
}