<?php

namespace App\Services\Riego;
use App\Models\ConsolidadoRiego as ResumenJornada;
use App\Models\ReporteDiarioRiego as RegistroDiario;
use App\Support\CalculoHelper;
use Illuminate\Support\Carbon;
class ConsolidadorServicio
{
    // Sin transaction — el proceso lo envuelve

    public function consolidar(ResumenJornada $resumen): void
    {
        $registros = $resumen->registrosDiarios()->where('por_acumulacion', false)->get();

        $minutosRiego        = 0;
        $minutosObservaciones = 0;
        $horaInicio          = null;
        $horaFin             = null;
        $intervalosJornal    = [];

        foreach ($registros as $reg) {
            $inicio = Carbon::parse($reg->hora_inicio);
            $fin    = Carbon::parse($reg->hora_fin);
            $diff   = $inicio->diffInMinutes($fin);

            if (!$horaInicio || $reg->hora_inicio < $horaInicio) $horaInicio = $reg->hora_inicio;
            if (!$horaFin    || $reg->hora_fin    > $horaFin)    $horaFin    = $reg->hora_fin;

            if (!$reg->sh) {
                $intervalosJornal[] = [
                    'hora_inicio' => $reg->hora_inicio,
                    'hora_fin'    => $reg->hora_fin,
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

        // Lo que supera 480 se acumula para el futuro
        $minutosAcumuladosNuevos = 0;
        if ($minutosJornalTotal > 480) {
            $minutosAcumuladosNuevos = $minutosJornalTotal - 480;
            $minutosJornalTotal = 480;
        }

        $resumen->hora_inicio           = $horaInicio;
        $resumen->hora_fin              = $horaFin;
        $resumen->minutos_regados       = $minutosRiego;
        $resumen->total_horas_observaciones = $this->toTime($minutosObservaciones);
        $resumen->minutos_jornal        = $minutosJornalTotal;
        $resumen->minutos_acumulados    = $minutosAcumuladosNuevos;
        $resumen->estado                = 'consolidado';
        $resumen->save();
    }

    private function toTime(int $minutos): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutos, 60), $minutos % 60);
    }
}