<?php

namespace App\Console\Commands;

use App\Models\ConsolidadoRiego;
use App\Models\ParametroTemporal;
use App\Models\ReporteDiarioRiego;
use App\Services\Riego\ConsolidadorServicio;
use App\Services\Riego\VerificacionSincronizacionRiegoServicio;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Regadores con "No acumular horas" (cuadrilleros por defecto): el jornal se topaba en el límite diario y lo trabajado
 * de más se perdía (ni jornal ni bolsa). Recalcula esos días con la regla corregida (se paga todo lo trabajado).
 * Sin --aplicar solo muestra la lista.
 */
class RiegoRecalcularNoAcumulaCommand extends Command
{
    protected $signature = 'riego:recalcular-no-acumula {--aplicar : Recalcula los días encontrados}';

    protected $description = 'Recalcula el jornal de riego de los días "No acumular horas" que quedaron topados en el límite diario';

    public function handle(): int
    {
        $afectados = ConsolidadoRiego::where('no_acumular_horas', true)->where('minutos_jornal', '>', 0)->orderBy('fecha')->get()
            ->filter(function ($c) {
                $tope = ParametroTemporal::limiteMinutosDiarios(Carbon::parse($c->fecha)->toDateString());
                $real = (int) round(ReporteDiarioRiego::where('consolidado_id', $c->id)->where('por_acumulacion', false)->sum('horas_ponderadas') * 60);
                // Tolerancia por redondeo de las horas ponderadas (6 tramos de 1.17 h = 7.02 h, no 7 h)
                return (int) $c->minutos_jornal === $tope && $real > $tope + 3;
            });

        if ($afectados->isEmpty()) {
            $this->info('No hay días topados por "No acumular horas".');
            return self::SUCCESS;
        }

        $fechas = [];
        foreach ($afectados as $c) {
            $antes = (int) $c->minutos_jornal;
            if ($this->option('aplicar')) {
                DB::transaction(fn() => app(ConsolidadorServicio::class)->consolidar($c, $c->hora_inicio_almuerzo, $c->hora_fin_almuerzo));
                $c->refresh();
                $fechas[] = Carbon::parse($c->fecha)->toDateString();
            }
            $this->line(Carbon::parse($c->fecha)->format('d/m/Y') . "  {$c->trabajador_nombre}  jornal " . round($antes / 60, 2) . ' h'
                . ($this->option('aplicar') ? ' → ' . round($c->minutos_jornal / 60, 2) . ' h' : ''));
        }

        if (!$this->option('aplicar')) {
            $this->warn("{$afectados->count()} día(s). Ejecuta con --aplicar para recalcularlos.");
            return self::SUCCESS;
        }

        // Vuelve a verificar esos días (tarea "Registros de riego desincronizados") y la BDD de costos
        $verificacion = app(VerificacionSincronizacionRiegoServicio::class);
        foreach (array_unique($fechas) as $f) {
            $verificacion->verificarPorFecha($f);
        }
        $verificacion->detectarTareasPendientes();
        \App\Services\Costos\Consolidacion\BddManoObraServicio::registrarCambio(min($fechas), max($fechas));
        $this->info("{$afectados->count()} día(s) recalculados.");
        return self::SUCCESS;
    }
}
