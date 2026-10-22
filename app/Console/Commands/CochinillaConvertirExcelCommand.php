<?php

namespace App\Console\Commands;

use App\Services\Cochinilla\Excel\CochinillaExcelConversionProceso;
use Illuminate\Console\Command;

/** Conversión única del Excel histórico de cochinilla al formato oficial (un archivo por año). */
class CochinillaConvertirExcelCommand extends Command
{
    protected $signature = 'cochinilla:convertir-excel {ruta : Excel histórico (COCHINILLA … .xlsx)} {--carpeta=cochinilla/convertidos}';
    protected $description = 'Convierte el Excel histórico de cochinilla al formato oficial, un archivo por año';

    public function handle(CochinillaExcelConversionProceso $proceso): int
    {
        ini_set('memory_limit', '4G');
        $this->info('Leyendo el Excel histórico (puede tardar unos minutos)…');
        $r = $proceso->convertir($this->argument('ruta'), $this->option('carpeta'));
        foreach ($r['resumen'] as $anio => $x) {
            $this->line("{$anio}: {$x['lotes']} lotes, {$x['sublotes']} sublotes, {$x['venteado']} venteados, {$x['filtrado']} filtrados, {$x['infestacion']} infestaciones · "
                . collect($x['extras'])->map(fn($n, $h) => "{$h} {$n}")->implode(', '));
        }
        $this->info('Archivos en storage/app/' . $this->option('carpeta'));
        $this->warn(count($r['problemas']) . ' problema(s) encontrados:');
        foreach ($r['problemas'] as $p) {
            $this->line("  - {$p}");
        }
        file_put_contents(storage_path('app/' . $this->option('carpeta') . '/PROBLEMAS.txt'), implode(PHP_EOL, $r['problemas']));
        return self::SUCCESS;
    }
}
