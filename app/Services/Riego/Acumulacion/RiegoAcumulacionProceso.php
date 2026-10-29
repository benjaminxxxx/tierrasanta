<?php

namespace App\Services\Riego\Acumulacion;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ReporteDiarioRiego;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Asigna los usos de horas acumuladas de un regador (reg_acumulacion_usos) en orden de fecha: cada día de uso toma lo
 * acumulado ANTES de ese día, lo más antiguo primero.
 *
 * Antes cada uso consumía lo más antiguo sin mirar el orden: si se registraba primero un uso del 01/10, se llevaba lo
 * acumulado el 01–08/09 y luego un uso del 09/09 veía saldo 0 (Claver, 29/10/2026). Reconstruir todo el regador tras
 * cada alta o baja deja cada uso con lo que de verdad tenía a su fecha.
 */
class RiegoAcumulacionProceso
{
    /**
     * @return array<string, int> fecha => minutos de uso que no se pudieron cubrir (usó más de lo que tenía)
     */
    public function reconstruir(string $trabajadorType, int $trabajadorId): array
    {
        return DB::transaction(function () use ($trabajadorType, $trabajadorId) {
            $consolidados = ConsolidadoRiego::where('trabajador_type', $trabajadorType)->where('trabajador_id', $trabajadorId)
                ->orderBy('fecha')->lockForUpdate()->get();
            $ids = $consolidados->pluck('id');

            AcumulacionUso::whereIn('consolidado_destino_id', $ids)->orWhereIn('consolidado_origen_id', $ids)->delete();

            // Lo que necesita cada día de uso (filas "por acumulación" del reporte de riego)
            $necesita = ReporteDiarioRiego::where('por_acumulacion', true)->whereIn('consolidado_id', $ids)->get()
                ->groupBy('consolidado_id')
                ->map(fn($g) => (int) $g->sum(fn($r) => Carbon::parse($r->hora_inicio)->diffInMinutes(Carbon::parse($r->hora_fin))));

            $saldo = []; // origen id => minutos que le quedan
            $faltantes = [];
            foreach ($consolidados as $c) {
                $fecha = Carbon::parse($c->fecha)->toDateString();
                $pendiente = (int) ($necesita[$c->id] ?? 0);
                // Solo lo acumulado en días anteriores (ya está en $saldo por el orden de fecha)
                foreach ($saldo as $origenId => $queda) {
                    if ($pendiente <= 0) {
                        break;
                    }
                    if ($queda <= 0) {
                        continue;
                    }
                    $tomar = min($queda, $pendiente);
                    AcumulacionUso::create(['consolidado_destino_id' => $c->id, 'consolidado_origen_id' => $origenId, 'minutos_consumidos' => $tomar]);
                    $saldo[$origenId] -= $tomar;
                    $pendiente -= $tomar;
                }
                if ($pendiente > 0) {
                    $faltantes[$fecha] = $pendiente;
                }
                if ((int) $c->minutos_acumulados > 0) {
                    $saldo[$c->id] = (int) $c->minutos_acumulados;
                }
            }

            foreach ($consolidados as $c) {
                $usado = (int) $c->minutos_acumulados - (int) ($saldo[$c->id] ?? $c->minutos_acumulados);
                if ((int) $c->minutos_utilizados !== $usado) {
                    $c->update(['minutos_utilizados' => $usado]);
                }
            }
            return $faltantes;
        });
    }
}
