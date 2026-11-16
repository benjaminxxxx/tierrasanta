<?php

namespace App\Console\Commands;

use App\Services\Campania\Sector\CampaniaSectorProceso;
use Illuminate\Console\Command;

/**
 * Arma los grupos de hileras y sus etapas de cada campaña con las labores registradas (mapa de etapas).
 * Respeta las campañas corregidas a mano, salvo --forzar.
 */
class CampaniaDetectarSectoresCommand extends Command
{
    protected $signature = 'campania:detectar-sectores {--forzar : vuelve a detectar también las corregidas a mano}';

    protected $description = 'Detecta las siembras (rellenos) y sus etapas de cada campaña con lo registrado';

    public function handle(CampaniaSectorProceso $proceso): int
    {
        $r = $proceso->detectarTodas((bool) $this->option('forzar'));
        $this->info("  Mapa de etapas: {$r['campanias']} campaña(s) detectadas, {$r['con_varios_grupos']} con más de una siembra"
            . ($r['respetadas'] ? ", {$r['respetadas']} sin tocar (corregidas a mano o sin fecha de inicio)" : '') . '.');
        return self::SUCCESS;
    }
}
