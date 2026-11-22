<?php

namespace App\Console\Commands;

use App\Services\Evaluacion\Siembra\EvaluacionSiembraProceso;
use Illuminate\Console\Command;

/** Pasa las evaluaciones de población y brotes que no tienen siembra a la primera siembra de su campaña. */
class EvaluacionesVincularSiembrasCommand extends Command
{
    protected $signature = 'evaluaciones:vincular-siembras {--simular : Solo muestra cuántas pasaría, sin guardar}';

    protected $description = 'Pasa las evaluaciones sin siembra a la primera siembra de su campaña (se puede repetir sin riesgo)';

    public function handle(EvaluacionSiembraProceso $proceso): int
    {
        $simular = (bool) $this->option('simular');
        $r = $proceso->vincularTodas($simular);
        $this->info(($simular ? 'Simulación (no se guardó nada). ' : '')
            . "Campañas: {$r['campanias']} · población: {$r['poblacion']} · brotes: {$r['brotes']}");
        if ($r['sin_siembra'] > 0) {
            $this->warn("Campañas sin siembra para vincular: {$r['sin_siembra']} (revisa su fecha de inicio).");
        }
        return self::SUCCESS;
    }
}
