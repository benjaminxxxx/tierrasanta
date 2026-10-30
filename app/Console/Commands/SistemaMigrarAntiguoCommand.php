<?php

namespace App\Console\Commands;

use App\Services\Sistema\MigracionAntigua\SistemaMigracionAntiguaProceso;
use Illuminate\Console\Command;

/**
 * Convierte la base del sistema antiguo a la estructura actual, en una base NUEVA (la antigua solo se lee):
 *
 *   1. Crear la base vacía y migrarla:   CREATE DATABASE santarita_nuevo;  DB_DATABASE=santarita_nuevo php artisan migrate
 *   2. Convertir:                         DB_DATABASE=santarita_nuevo php artisan sistema:migrar-antiguo santarita_antiguo
 *      (para repetir sobre la misma base: --vaciar)
 *   3. Revisar el informe y la aplicación apuntando a santarita_nuevo.
 *   4. Exportar santarita_nuevo y reemplazar con ella la base del servidor antiguo; luego subir el código.
 *      `php artisan migrate` ya no tendrá nada pendiente.
 *
 * Ver App\Services\Sistema\MigracionAntigua\SistemaMigracionAntiguaProceso.
 */
class SistemaMigrarAntiguoCommand extends Command
{
    protected $signature = 'sistema:migrar-antiguo {origen : base con la estructura antigua (misma conexión MySQL)}
        {--vaciar : vacía la base destino antes de copiar (para repetir la conversión)}';

    protected $description = 'Copia la base del sistema antiguo a una base nueva con la estructura actual, convirtiendo lo que cambió';

    public function handle(SistemaMigracionAntiguaProceso $proceso): int
    {
        $destino = \Illuminate\Support\Facades\DB::getDatabaseName();
        $this->info("Origen (solo lectura): {$this->argument('origen')}  →  destino: {$destino}");
        if ($this->option('vaciar') && !$this->confirm("Se vaciará la base {$destino}. ¿Continuar?", true)) {
            return self::FAILURE;
        }

        try {
            $r = $proceso->ejecutar($this->argument('origen'), (bool) $this->option('vaciar'), fn($m) => $this->line($m));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Filas por tabla (solo las que cambian de cantidad o se llenaron por conversión)');
        $filas = collect($r['tablas'])->filter(fn($v) => $v['origen'] !== $v['destino'])
            ->map(fn($v, $t) => [$t, $v['origen'] ?? '(nueva)', $v['destino']])->values()->all();
        $this->table(['Tabla', 'Antigua', 'Nueva'], $filas);
        $iguales = collect($r['tablas'])->filter(fn($v) => $v['origen'] === $v['destino'])->count();
        $this->line("{$iguales} tablas con la misma cantidad de filas.");

        $this->info('Copias de lo que ya no existe en la estructura actual');
        $this->table(['Tabla', 'Filas'], collect($r['legado'])->map(fn($n, $t) => [$t, $n])->values()->all());

        $this->info('Valores recalculados por triggers distintos a la base antigua');
        $this->table(['Columna', 'Filas distintas'], collect($r['triggers'])->map(fn($n, $c) => [$c, $n])->values()->all());

        if ($r['huerfanas']) {
            $this->warn('Llaves que apuntan a registros inexistentes (ya venían así):');
            foreach ($r['huerfanas'] as $h) {
                $this->line("  {$h}");
            }
        }
        if ($r['avisos']) {
            $this->warn('Avisos (' . count($r['avisos']) . '):');
            foreach ($r['avisos'] as $a) {
                $this->line("  - {$a}");
            }
        }
        $this->info("Listo. Revisa la aplicación con DB_DATABASE={$destino} antes de reemplazar el servidor.");
        return self::SUCCESS;
    }
}
