<?php

namespace App\Services\Campania;

use App\Models\Actividad;
use App\Models\CampoCampania;
use App\Models\CuadActividadBono;
use App\Models\CuadDetalleHora;
use App\Models\CuadRegistroDiario;
use App\Models\PlanDetalleHora;
use App\Support\CalculoHelper;
use Exception;
use Illuminate\Support\Carbon;

class CampaniaServicio
{
    public function obtenerCostosManoObra($campania)
    {

        $campo = $campania->campo;
        $fechaInicio = $campania->fecha_inicio;
        $fechaFin = $campania->fecha_fin ?? now();

        $detalleHoras = CuadDetalleHora::with(['registroDiario.detalleHoras'])->whereHas('registroDiario', function ($registroDiario) use ($fechaInicio, $fechaFin) {
            return $registroDiario->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        })
            ->where('campo_nombre', $campo)
            ->get();

        $planDetalleHoras = PlanDetalleHora::with(['registroDiario.detalles'])->whereHas('registroDiario', function ($registroDiario) use ($fechaInicio, $fechaFin) {
            return $registroDiario->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        })
            ->where('campo_nombre', $campo)
            ->get();

        $data = [];
        foreach ($detalleHoras as $detalleHora) {
            $registro = $detalleHora->registroDiario;

            if (!$registro->coincide_total_horas) {
                throw new Exception("En alguna fecha no se ha detallado las actividades y por ende el total de horas no coincide, corregir para hacer un calculo mas preciso.");
            }
            $actividad = Actividad::where('fecha', $registro->fecha)
                ->where('campo', $detalleHora->campo_nombre)
                ->where('labor_id', $detalleHora->codigo_labor)->first();

            if (!$actividad) {
                throw new Exception("No existe una actividad con campo {$detalleHora->campo_nombre} en codigo de labor {$detalleHora->codigo_labor} en la fecha {$registro->fecha}");

            }
            $campoNombre = $detalleHora->campo_nombre;
            $codigoLabor = $detalleHora->codigo_labor;
            // 🔹 Calcular horas del tramo actual
            $horaInicio = Carbon::parse($detalleHora->hora_inicio);
            $horaFin = Carbon::parse($detalleHora->hora_fin);
            $horasTramo = $horaInicio->diffInMinutes($horaFin) / 60;

            // 🔹 Buscar el bono total para esta actividad del mismo registro diario
            $bonoActividad = CuadActividadBono::where('registro_diario_id', $registro->id)
                ->where('actividad_id', $actividad->id)
                ->first();

            $totalBono = $bonoActividad->total_bono ?? 0;

            $totalHorasLabor = $registro->detalleHoras
                ->filter(
                    fn($h) =>
                    $h->campo_nombre === $campoNombre &&
                    $h->codigo_labor === $codigoLabor
                )
                ->sum(function ($h) {
                    $inicio = Carbon::parse($h->hora_inicio);
                    $fin = Carbon::parse($h->hora_fin);
                    return $inicio->diffInMinutes($fin) / 60;
                });
            // 🔹 Calcular bono proporcional
            $bonoParcial = $totalHorasLabor > 0
                ? ($horasTramo / $totalHorasLabor) * $totalBono
                : 0;

            // 🔹 Calcular costo total (proporcional + bono)
            $costo = CalculoHelper::calcularCostoActividad(
                totalHoras: $registro->total_horas,
                totalJornal: $registro->costo_dia,
                horasParcial: $horasTramo,
                bonoParcial: $bonoParcial
            );
            $data[$campoNombre][$codigoLabor][] = [
                'tipo_empleado' => 'cuadrillero',
                'empleado' => $registro->cuadrillero->nombres,
                'hora_inicio' => $detalleHora->hora_inicio,
                'hora_fin' => $detalleHora->hora_fin,
                'horas' => round($horasTramo, 2),
                'total_horas_labor' => round($totalHorasLabor, 2),
                'bono_parcial' => round($bonoParcial, 2),
                'total_bono' => $totalBono,
                'costo' => round($costo, 2),
                'costo_sin_bono' => round($costo - $bonoParcial, 2),
                'total_horas_dia' => $registro->total_horas,
                'jornal_dia' => $registro->costo_dia,
            ];
        }
        foreach ($planDetalleHoras as $detalleHora) {
            $registro = $detalleHora->registroDiario;
/*
            if (!$registro->coincide_total_horas) {
                throw new Exception("En alguna fecha no se ha detallado las actividades y por ende el total de horas no coincide, corregir para hacer un calculo mas preciso.");
            }*/
            $actividad = Actividad::where('fecha', $registro->fecha)
                ->where('campo', $detalleHora->campo_nombre)
                ->where('labor_id', $detalleHora->codigo_labor)->first();

            if (!$actividad) {
                throw new Exception("No existe una actividad con campo {$detalleHora->campo_nombre} en codigo de labor {$detalleHora->codigo_labor} en la fecha {$registro->fecha}");

            }
            $campoNombre = $detalleHora->campo_nombre;
            $codigoLabor = $detalleHora->codigo_labor;
            // 🔹 Calcular horas del tramo actual
            $horaInicio = Carbon::parse($detalleHora->hora_inicio);
            $horaFin = Carbon::parse($detalleHora->hora_fin);
            $horasTramo = $horaInicio->diffInMinutes($horaFin) / 60;

            // 🔹 Buscar el bono total para esta actividad del mismo registro diario
            $bonoActividad = CuadActividadBono::where('registro_diario_id', $registro->id)
                ->where('actividad_id', $actividad->id)
                ->first();

            $totalBono = $bonoActividad->total_bono ?? 0;

            $totalHorasLabor = $registro->detalleHoras
                ->filter(
                    fn($h) =>
                    $h->campo_nombre === $campoNombre &&
                    $h->codigo_labor === $codigoLabor
                )
                ->sum(function ($h) {
                    $inicio = Carbon::parse($h->hora_inicio);
                    $fin = Carbon::parse($h->hora_fin);
                    return $inicio->diffInMinutes($fin) / 60;
                });
            // 🔹 Calcular bono proporcional
            $bonoParcial = $totalHorasLabor > 0
                ? ($horasTramo / $totalHorasLabor) * $totalBono
                : 0;

            // 🔹 Calcular costo total (proporcional + bono)
            $costo = CalculoHelper::calcularCostoActividad(
                totalHoras: $registro->total_horas,
                totalJornal: $registro->costo_dia,
                horasParcial: $horasTramo,
                bonoParcial: $bonoParcial
            );
            $data[$campoNombre][$codigoLabor][] = [
                'tipo_empleado' => 'cuadrillero',
                'empleado' => $registro->cuadrillero->nombres,
                'hora_inicio' => $detalleHora->hora_inicio,
                'hora_fin' => $detalleHora->hora_fin,
                'horas' => round($horasTramo, 2),
                'total_horas_labor' => round($totalHorasLabor, 2),
                'bono_parcial' => round($bonoParcial, 2),
                'total_bono' => $totalBono,
                'costo' => round($costo, 2),
                'costo_sin_bono' => round($costo - $bonoParcial, 2),
                'total_horas_dia' => $registro->total_horas,
                'jornal_dia' => $registro->costo_dia,
            ];
        }
        dd($data);

        return $registrosDiarios;
    }
}
