<?php

namespace App\Console\Commands;

use App\Services\Campania\Sector\CampaniaSectorProceso;
use Illuminate\Console\Command;

/** Enlaza las siembras del mapa de etapas de todas las campañas con el registro de siembras (Campo → Siembras). */
class CampaniaSincronizarSiembrasCommand extends Command
{
    protected $signature = 'campania:sincronizar-siembras';

    protected $description = 'Enlaza las siembras del mapa de etapas con el registro de siembras (crea las que falten)';

    public function handle(CampaniaSectorProceso $proceso): int
    {
        $n = $proceso->sincronizarTodas();
        $this->info("Campañas enlazadas con el registro de siembras: {$n}");
        return self::SUCCESS;
    }
}
