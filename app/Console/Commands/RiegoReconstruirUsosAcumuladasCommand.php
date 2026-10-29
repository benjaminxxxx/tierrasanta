<?php

namespace App\Console\Commands;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ReporteDiarioRiego;
use App\Services\Riego\Acumulacion\RiegoAcumulacionProceso;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reasigna los usos de horas acumuladas de riego (reg_acumulacion_usos) de cada regador en orden de fecha: cada día de
 * uso toma lo acumulado ANTES de ese día, lo más antiguo primero (RiegoAcumulacionProceso). Corrige usos que faltaban
 * y usos que se llevaron horas que otro día anterior necesitaba (saldo 0 al querer usar, caso Claver 09/09).
 */
class RiegoReconstruirUsosAcumuladasCommand extends Command
{
    protected $signature = 'riego:reconstruir-usos-acumuladas {--verificar : Solo informa, no cambia nada}';

    protected $description = 'Reasigna en orden de fecha los usos de horas acumuladas de riego (banco de horas)';

    public function handle(): int
    {
        $usos = ReporteDiarioRiego::where('por_acumulacion', true)->get()->groupBy('consolidado_id')
            ->map(fn($g) => (int) $g->sum(fn($r) => Carbon::parse($r->hora_inicio)->diffInMinutes(Carbon::parse($r->hora_fin))));
        $destinos = ConsolidadoRiego::whereIn('id', $usos->keys())->orderBy('fecha')->get();
        $trabajadores = $destinos->unique(fn($d) => $d->trabajador_type . ':' . $d->trabajador_id);

        $incompletos = $destinos->filter(fn($d) => (int) AcumulacionUso::where('consolidado_destino_id', $d->id)->sum('minutos_consumidos') < $usos[$d->id]);
        $this->info("{$destinos->count()} día(s) con uso de acumuladas de {$trabajadores->count()} regador(es); {$incompletos->count()} sin sus usos completos.");
        foreach ($incompletos as $d) {
            $this->line('  ' . Carbon::parse($d->fecha)->format('d/m/Y') . " {$d->trabajador_nombre}: usó " . round($usos[$d->id] / 60, 2) . ' h');
        }
        if ($this->option('verificar')) {
            return self::SUCCESS;
        }

        $proceso = app(RiegoAcumulacionProceso::class);
        foreach ($trabajadores as $t) {
            foreach ($proceso->reconstruir($t->trabajador_type, (int) $t->trabajador_id) as $fecha => $min) {
                $this->warn('  ' . Carbon::parse($fecha)->format('d/m/Y') . " {$t->trabajador_nombre}: faltaron " . round($min / 60, 2) . ' h (usó más de lo que tenía acumulado antes de ese día)');
            }
        }
        $this->info('Listo: usos reasignados en orden de fecha.');
        return self::SUCCESS;
    }
}
