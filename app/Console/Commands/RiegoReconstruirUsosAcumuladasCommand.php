<?php

namespace App\Console\Commands;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ReporteDiarioRiego;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Días con uso de horas acumuladas (filas "por acumulación" del reporte de riego) que no tienen sus usos registrados
 * (reg_acumulacion_usos): el banco de horas parecía intacto y el saldo salía inflado. Se reconstruyen en orden de
 * fecha, tomando lo acumulado ANTES de cada día de uso (lo más antiguo primero).
 */
class RiegoReconstruirUsosAcumuladasCommand extends Command
{
    protected $signature = 'riego:reconstruir-usos-acumuladas {--verificar : Solo informa, no cambia nada}';

    protected $description = 'Registra los usos de horas acumuladas de riego que faltan (banco de horas)';

    public function handle(): int
    {
        $usosPorDestino = ReporteDiarioRiego::where('por_acumulacion', true)->get()
            ->groupBy('consolidado_id')
            ->map(fn($g) => (int) $g->sum(fn($r) => Carbon::parse($r->hora_inicio)->diffInMinutes(Carbon::parse($r->hora_fin))));
        $destinos = ConsolidadoRiego::whereIn('id', $usosPorDestino->keys())->orderBy('fecha')->get();

        $faltan = $destinos->filter(fn($d) => AcumulacionUso::where('consolidado_destino_id', $d->id)->sum('minutos_consumidos') < $usosPorDestino[$d->id]);
        $this->info("{$destinos->count()} día(s) con uso de acumuladas; {$faltan->count()} sin sus usos completos.");
        if ($this->option('verificar') || $faltan->isEmpty()) {
            foreach ($faltan as $d) {
                $this->line('  ' . Carbon::parse($d->fecha)->format('d/m/Y') . " {$d->trabajador_nombre}: usó " . round($usosPorDestino[$d->id] / 60, 2) . ' h');
            }
            return self::SUCCESS;
        }

        DB::transaction(function () use ($faltan, $usosPorDestino) {
            foreach ($faltan as $d) {
                $registrado = (int) AcumulacionUso::where('consolidado_destino_id', $d->id)->sum('minutos_consumidos');
                $pendiente = $usosPorDestino[$d->id] - $registrado;
                $fecha = Carbon::parse($d->fecha)->toDateString();
                $origenes = ConsolidadoRiego::where('trabajador_type', $d->trabajador_type)->where('trabajador_id', $d->trabajador_id)
                    ->whereRaw('minutos_acumulados > minutos_utilizados')->whereDate('fecha', '<', $fecha)->orderBy('fecha')->lockForUpdate()->get();
                foreach ($origenes as $o) {
                    if ($pendiente <= 0) {
                        break;
                    }
                    $tomar = min($pendiente, (int) $o->minutos_acumulados - (int) $o->minutos_utilizados);
                    $o->increment('minutos_utilizados', $tomar);
                    $uso = AcumulacionUso::firstOrNew(['consolidado_destino_id' => $d->id, 'consolidado_origen_id' => $o->id]);
                    $uso->minutos_consumidos = (int) $uso->minutos_consumidos + $tomar;
                    $uso->save();
                    $pendiente -= $tomar;
                }
                $linea = Carbon::parse($d->fecha)->format('d/m/Y') . " {$d->trabajador_nombre}: " . round(($usosPorDestino[$d->id] - $registrado - max(0, $pendiente)) / 60, 2) . ' h registradas';
                $pendiente > 0
                    ? $this->warn("  {$linea}; faltaron " . round($pendiente / 60, 2) . ' h (usó más de lo que tenía acumulado)')
                    : $this->line("  {$linea}");
            }
        });

        $this->info('Listo.');
        return self::SUCCESS;
    }
}
