<?php

namespace App\Services\Riego;
use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego as ResumenJornada;
use App\Models\ReporteDiarioRiego as RegistroDiario;
// app/Services/Riego/AcumulacionServicio.php
class AcumulacionServicio
{
    // Sin transaction — el proceso lo envuelve

    public function consumirFIFO(ResumenJornada $destino, int $minutosAUsar): void
    {
        $pendiente = $minutosAUsar;

        ResumenJornada::where('trabajador_type', $destino->trabajador_type)
            ->where('trabajador_id', $destino->trabajador_id)
            ->whereRaw('minutos_acumulados > minutos_utilizados')
            ->orderBy('fecha')
            ->each(function ($origen) use (&$pendiente, $destino) {
                if ($pendiente <= 0) return false;

                $disponible = $origen->minutos_acumulados - $origen->minutos_utilizados;
                $consumir   = min($disponible, $pendiente);

                $origen->increment('minutos_utilizados', $consumir);

                AcumulacionUso::updateOrCreate(
                    [
                        'consolidado_destino_id' => $destino->id,
                        'consolidado_origen_id'  => $origen->id,
                    ],
                    ['minutos_consumidos' => $consumir]
                );

                $pendiente -= $consumir;
            });
    }

    public function liberarUsos(ResumenJornada $destino): void
    {
        AcumulacionUso::where('consolidado_destino_id', $destino->id)
            ->each(function ($uso) {
                $uso->consolidadoOrigen->decrement('minutos_utilizados', $uso->minutos_consumidos);
                $uso->delete();
            });
    }

    public function minutosDisponibles(ResumenJornada $resumen): int
    {
        $acumulado = ResumenJornada::where('trabajador_type', $resumen->trabajador_type)
            ->where('trabajador_id', $resumen->trabajador_id)
            ->sum('minutos_acumulados');

        $utilizado = AcumulacionUso::whereHas('consolidadoOrigen', fn($q) =>
            $q->where('trabajador_type', $resumen->trabajador_type)
              ->where('trabajador_id', $resumen->trabajador_id)
        )->sum('minutos_consumidos');

        return max(0, $acumulado - $utilizado);
    }
}