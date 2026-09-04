<?php

namespace App\Services\Riego;
use App\Models\ConsolidadoRiego as ResumenJornada;
use App\Models\ParametroTemporal;
use App\Support\CalculoHelper;
use Illuminate\Support\Carbon;
class ConsolidadorServicio
{
    // Sin transaction — el proceso lo envuelve
/*
    public function consolidar(ResumenJornada $resumen,$horaInicioAlmuerzo,$horaFinAlmuerzo): void
    {
        $registros = $resumen->registrosDiarios()->where('por_acumulacion', false)->get();

        $minutosRiego = 0;
        $minutosObservaciones = 0;
        $horaInicio = null;
        $horaFin = null;
        $intervalosJornal = [];

        foreach ($registros as $reg) {
            $inicio = Carbon::parse($reg->hora_inicio);
            $fin = Carbon::parse($reg->hora_fin);
            $diff = $inicio->diffInMinutes($fin);

            if (!$horaInicio || $reg->hora_inicio < $horaInicio)
                $horaInicio = $reg->hora_inicio;
            if (!$horaFin || $reg->hora_fin > $horaFin)
                $horaFin = $reg->hora_fin;

            if (!$reg->sh) {
                $intervalosJornal[] = [
                    'hora_inicio' => $reg->hora_inicio,
                    'hora_fin' => $reg->hora_fin,
                ];
            }

            if (mb_strtolower($reg->tipo_labor) === 'riego') {
                $minutosRiego += $diff;
            } else {
                $minutosObservaciones += $diff;
            }
        }

        $minutosJornalBruto = empty($intervalosJornal)
            ? 0
            : CalculoHelper::calcularMinutosJornalParcial($intervalosJornal);

        if (!$resumen->descuento_horas_almuerzo) {
            $minutosJornalBruto = max(0, $minutosJornalBruto - 60);
        }

        // Sumar los minutos del registro de acumulación usado hoy
        $minutosAcumuladosUsadosHoy = 0;
        $registroAcumulado = $resumen->registrosDiarios()
            ->where('por_acumulacion', true)
            ->first();

        if ($registroAcumulado) {
            $minutosAcumuladosUsadosHoy = Carbon::parse($registroAcumulado->hora_inicio)
                ->diffInMinutes(Carbon::parse($registroAcumulado->hora_fin));
        }

        $minutosJornalTotal = $minutosJornalBruto + $minutosAcumuladosUsadosHoy;


        $limiteMinutos = ParametroTemporal::limiteMinutosDiarios(
            $resumen->fecha
        );
        
        // Lo que supera 480 se acumula para el futuro
        $minutosAcumuladosNuevos = 0;
        if ($minutosJornalTotal > $limiteMinutos && !$resumen->no_acumular_horas) {
            $minutosAcumuladosNuevos = $minutosJornalTotal - $limiteMinutos;
            $minutosJornalTotal = $limiteMinutos;
        }

        $resumen->hora_inicio = $horaInicio;
        $resumen->hora_fin = $horaFin;
        $resumen->minutos_regados = $minutosRiego;
        $resumen->total_horas_observaciones = $this->toTime($minutosObservaciones);
        $resumen->minutos_jornal = $minutosJornalTotal;
        $resumen->minutos_acumulados = $minutosAcumuladosNuevos;
        $resumen->hora_inicio_almuerzo = $horaInicioAlmuerzo;
        $resumen->hora_fin_almuerzo = $horaFinAlmuerzo;
        $resumen->estado = 'consolidado';
        $resumen->save();
    }*/
    public function consolidar(
        ResumenJornada $resumen,
        ?string $horaInicioAlmuerzo = null,
        ?string $horaFinAlmuerzo = null
    ): void {
        // 1. Registros físicos no acumulados
        $registros = $resumen->registrosDiarios()->where('por_acumulacion', false)->get();

        $minutosRiego = 0;
        $minutosObservaciones = 0;
        $horaInicio = null;
        $horaFin = null;
        $intervalosJornal = [];
        $intervalosParaPonderar = [];

        foreach ($registros as $reg) {
            $inicio = Carbon::parse($reg->hora_inicio);
            $fin = Carbon::parse($reg->hora_fin);
            $diff = $inicio->diffInMinutes($fin);

            if (!$horaInicio || $reg->hora_inicio < $horaInicio) {
                $horaInicio = $reg->hora_inicio;
            }
            if (!$horaFin || $reg->hora_fin > $horaFin) {
                $horaFin = $reg->hora_fin;
            }

            // Si NO es Sin Haberes (sh = false), entra al cálculo de presencia y ponderación por concurrencia
            if (!$reg->sh) {
                $intervalosJornal[] = [
                    'hora_inicio' => $reg->hora_inicio,
                    'hora_fin' => $reg->hora_fin,
                ];
                
                $intervalosParaPonderar[$reg->id] = [
                    'hora_inicio' => $reg->hora_inicio,
                    'hora_fin' => $reg->hora_fin,
                    'nombre' => $reg->campo,
                ];
            }

            if (mb_strtolower($reg->tipo_labor) === 'riego') {
                $minutosRiego += $diff;
            } else {
                $minutosObservaciones += $diff;
            }
        }

        // 2. Cálculo de tiempo de presencia real (fusionando solapamientos)
        $minutosJornalBruto = empty($intervalosJornal)
            ? 0
            : CalculoHelper::calcularMinutosJornalParcial($intervalosJornal);

        $minutosAlmuerzo = 0;

        if ($horaInicioAlmuerzo && $horaFinAlmuerzo) {
            $inicioAlm = Carbon::parse($horaInicioAlmuerzo);
            $finAlm = Carbon::parse($horaFinAlmuerzo);

            if ($finAlm > $inicioAlm) {
                $minutosAlmuerzo = $inicioAlm->diffInMinutes($finAlm);
            }
        }

        // Descuento de almuerzo real si corresponde según bandera
        if ($minutosAlmuerzo > 0) {
            $minutosJornalBruto = max(0, $minutosJornalBruto - $minutosAlmuerzo);
        }
        // Sumar minutos consumidos de la bolsa de acumulación
        $minutosAcumuladosUsadosHoy = 0;
        $registroAcumulado = $resumen->registrosDiarios()
            ->where('por_acumulacion', true)
            ->first();

        if ($registroAcumulado) {
            $minutosAcumuladosUsadosHoy = Carbon::parse($registroAcumulado->hora_inicio)
                ->diffInMinutes(Carbon::parse($registroAcumulado->hora_fin));
        }

        $minutosJornalTotal = $minutosJornalBruto + $minutosAcumuladosUsadosHoy;

        // Límite diario dinámico (ej. 480 min)
        $limiteMinutos = ParametroTemporal::limiteMinutosDiarios($resumen->fecha);

        $minutosAcumuladosNuevos = 0;
        if ($minutosJornalTotal > $limiteMinutos && !$resumen->no_acumular_horas) {
            $minutosAcumuladosNuevos = $minutosJornalTotal - $limiteMinutos;
            $minutosJornalTotal = $limiteMinutos; // Tope de pago para hoy
        }

        // 1. Reparto por concurrencia a 2 decimales (compensa residuos sobre las 9.5h reales)
        $calculo = CalculoHelper::calcularHorasPonderadasConExplicacion(
            $intervalosParaPonderar,
            $horaInicioAlmuerzo,
            $horaFinAlmuerzo
        );

        // 2. Asignación directa del costo/esfuerzo real a cada registro (Sin achicar horas)
        foreach ($registros as $reg) {
            if ($reg->sh) {
                $reg->horas_ponderadas = 0.00;
            } else {
                $reg->horas_ponderadas = $calculo['totales'][$reg->id] ?? 0.00;
            }
            $reg->save();
        }

        // 3. Control de Planilla / Bolsa (Guarda 9.0h en resumen y 0.5h en acumulados)
        $limiteMinutos = ParametroTemporal::limiteMinutosDiarios($resumen->fecha); // 540 min (9h)

        $minutosAcumuladosNuevos = 0;
        if ($minutosJornalBruto > $limiteMinutos && !$resumen->no_acumular_horas) {
            $minutosAcumuladosNuevos = $minutosJornalBruto - $limiteMinutos; // 30 min a la bolsa
            $minutosJornalTotal = $limiteMinutos;                           // 9.0h a la planilla
        } else {
            $minutosJornalTotal = $minutosJornalBruto;
        }

        // 5. Guardar Resumen Consolidado
        $resumen->hora_inicio = $horaInicio;
        $resumen->hora_fin = $horaFin;
        $resumen->minutos_regados = $minutosRiego;
        $resumen->total_horas_observaciones = $this->toTime($minutosObservaciones);
        $resumen->minutos_jornal = $minutosJornalTotal;
        $resumen->minutos_acumulados = $minutosAcumuladosNuevos;
        $resumen->hora_inicio_almuerzo = $horaInicioAlmuerzo;
        $resumen->hora_fin_almuerzo = $horaFinAlmuerzo;
        $resumen->estado = 'consolidado';
        $resumen->explicacion_jornal_computable = $calculo['explicacion'];
        $resumen->save();
    }
    private function toTime(int $minutos): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutos, 60), $minutos % 60);
    }
}