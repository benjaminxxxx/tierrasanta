<?php
// app/Services/GestionCuadrilla/RangoPeriodoServicio.php
namespace App\Services\GestionCuadrilla;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class RangoPeriodoServicio
{
    // Semana de cuadrilla: lunes a sábado, relativa a $fechaReferencia
    // (antes era domingo-viernes sobre "hoy"; ahora ancla a la fecha del desglose)
    public function semanaDe(Carbon|string $fechaReferencia): array
    {
        $inicio = Carbon::parse($fechaReferencia)->startOfWeek(CarbonInterface::MONDAY);
        $fin = $inicio->copy()->addDays(5); // sábado

        return [$inicio->toDateString(), $fin->toDateString()];
    }

    // Esa semana + la anterior
    public function quincenaDe(Carbon|string $fechaReferencia): array
    {
        [$inicioSemana, $finSemana] = $this->semanaDe($fechaReferencia);

        return [Carbon::parse($inicioSemana)->subWeek()->toDateString(), $finSemana];
    }

    // Mes anterior al de $fechaReferencia
    public function mesAnteriorDe(Carbon|string $fechaReferencia): array
    {
        $mesAnterior = Carbon::parse($fechaReferencia)->subMonthNoOverflow();

        return [
            $mesAnterior->copy()->startOfMonth()->toDateString(),
            $mesAnterior->copy()->endOfMonth()->toDateString(),
        ];
    }

    public function mesEspecifico(int $anio, int $mes): array
    {
        $fecha = Carbon::createFromDate($anio, $mes, 1);

        return [
            $fecha->copy()->startOfMonth()->toDateString(),
            $fecha->copy()->endOfMonth()->toDateString(),
        ];
    }
}