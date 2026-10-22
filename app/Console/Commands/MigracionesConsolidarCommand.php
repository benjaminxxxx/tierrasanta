<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Pasa una base existente (dominio antiguo) a las migraciones consolidadas (una por tabla, 2026_09_01_*):
 * 1. Comprueba que todas las migraciones anteriores (legacy/database/migrations) estén ejecutadas.
 * 2. Compara el esquema real con el que crean las migraciones nuevas (database/consolidacion/esquema.json).
 * 3. Si todo coincide (o con --forzar), reemplaza los registros de la tabla migrations por los nombres nuevos,
 *    como si se hubieran ejecutado. No toca ninguna tabla ni dato.
 *
 * --generar-esquema: guarda el esquema de la base actual como referencia (solo en una instalación limpia).
 */
class MigracionesConsolidarCommand extends Command
{
    protected $signature = 'migraciones:consolidar
        {--verificar : Solo informa, no cambia nada}
        {--forzar : Reemplaza los registros aunque el esquema tenga diferencias}
        {--generar-esquema : Guarda el esquema de esta base como referencia}';

    protected $description = 'Marca las migraciones consolidadas como ejecutadas en una base existente';

    private const PREFIJO_NUEVAS = '2026_09_01_';
    private const ESQUEMA = 'database/consolidacion/esquema.json';

    public function handle(): int
    {
        if ($this->option('generar-esquema')) {
            File::ensureDirectoryExists(base_path(dirname(self::ESQUEMA)));
            $esquema = $this->esquema();
            File::put(base_path(self::ESQUEMA), json_encode($esquema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
            $this->info('Esquema guardado: ' . count($esquema) . ' elementos.');
            return self::SUCCESS;
        }

        $anteriores = collect(File::files(base_path('legacy/database/migrations')))->map(fn($f) => $f->getFilenameWithoutExtension())->sort()->values();
        $nuevas = collect(File::files(database_path('migrations')))->map(fn($f) => $f->getFilenameWithoutExtension())
            ->filter(fn($n) => str_starts_with($n, self::PREFIJO_NUEVAS))->sort()->values();
        $registradas = DB::table('migrations')->pluck('migration');

        if ($registradas->intersect($nuevas)->isNotEmpty()) {
            $this->info('Esta base ya usa las migraciones consolidadas.');
            return self::SUCCESS;
        }

        // 1. Migraciones anteriores pendientes
        $pendientes = $anteriores->diff($registradas)->values();
        if ($pendientes->isNotEmpty()) {
            $this->error("Faltan ejecutar {$pendientes->count()} migraciones anteriores:");
            $pendientes->each(fn($m) => $this->line("  - $m"));
            $this->line('Ejecútalas primero: php artisan migrate --path=legacy/database/migrations');
            return self::FAILURE;
        }
        $desconocidas = $registradas->diff($anteriores)->values();
        if ($desconocidas->isNotEmpty()) {
            $this->warn('Registros en migrations que no están en legacy/database/migrations (se quitarán): ' . $desconocidas->implode(', '));
        }

        // 2. Esquema real vs. esquema de las migraciones nuevas
        $esperado = json_decode(File::get(base_path(self::ESQUEMA)), true);
        $real = $this->esquema();
        $faltan = array_values(array_diff($esperado, $real));
        $sobran = array_values(array_diff($real, $esperado));
        if ($faltan || $sobran) {
            $this->warn('El esquema de esta base no coincide con el de las migraciones nuevas:');
            foreach ($faltan as $l) {
                $this->line("  <fg=red>esperado:</> $l");
            }
            foreach ($sobran as $l) {
                $this->line("  <fg=yellow>en la base:</> $l");
            }
        } else {
            $this->info('El esquema coincide con las migraciones nuevas.');
        }

        if ($this->option('verificar')) {
            return ($faltan || $sobran) ? self::FAILURE : self::SUCCESS;
        }
        if (($faltan || $sobran) && !$this->option('forzar')) {
            $this->error('No se cambió nada. Corrige las diferencias o usa --forzar si son aceptables.');
            return self::FAILURE;
        }
        if (!$this->confirm("Se reemplazarán {$registradas->count()} registros de migrations por {$nuevas->count()} consolidados. ¿Continuar?", true)) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($nuevas) {
            DB::table('migrations')->delete();
            DB::table('migrations')->insert($nuevas->map(fn($m) => ['migration' => $m, 'batch' => 1])->all());
        });
        $this->info("Listo: {$nuevas->count()} migraciones consolidadas registradas como ejecutadas.");
        return self::SUCCESS;
    }

    /**
     * Esquema comparable: columnas (tipo, nulo, default, extra), índices, llaves foráneas y triggers.
     * No compara el orden de las columnas ni los comentarios.
     *
     * @return string[]
     */
    private function esquema(): array
    {
        $db = DB::getDatabaseName();
        $l = [];
        foreach (DB::select("SELECT TABLE_NAME t, COLUMN_NAME c, COLUMN_TYPE ty, IS_NULLABLE n, COLUMN_DEFAULT d, EXTRA e
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME<>'migrations'", [$db]) as $r) {
            $l[] = "columna {$r->t}.{$r->c} {$r->ty}" . ($r->n === 'YES' ? ' null' : '') . ($r->d !== null ? " default={$r->d}" : '') . ($r->e ? " {$r->e}" : '');
        }
        foreach (DB::select("SELECT TABLE_NAME t, INDEX_NAME i, NON_UNIQUE u, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) c
            FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME<>'migrations' GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE", [$db]) as $r) {
            $l[] = ($r->u ? 'indice' : 'unico') . " {$r->t}.{$r->i} ({$r->c})";
        }
        foreach (DB::select("SELECT k.TABLE_NAME t, k.CONSTRAINT_NAME n, GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION) c,
                k.REFERENCED_TABLE_NAME rt, GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION) rc, r.UPDATE_RULE ur, r.DELETE_RULE dr
            FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                ON r.CONSTRAINT_SCHEMA=k.TABLE_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME
            WHERE k.TABLE_SCHEMA=? GROUP BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, r.UPDATE_RULE, r.DELETE_RULE", [$db]) as $r) {
            $l[] = "llave {$r->t}.{$r->n} ({$r->c}) -> {$r->rt}({$r->rc}) al borrar {$r->dr}, al actualizar {$r->ur}";
        }
        foreach (DB::select("SELECT TRIGGER_NAME n, EVENT_OBJECT_TABLE t, ACTION_TIMING ti, EVENT_MANIPULATION e, ACTION_STATEMENT s
            FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=?", [$db]) as $r) {
            // El cuerpo se compara sin espacios para no depender de la sangría
            $l[] = "trigger {$r->n} {$r->ti} {$r->e} {$r->t} " . md5(preg_replace('/\s+/', '', $r->s));
        }
        foreach (DB::select("SELECT TABLE_NAME n FROM information_schema.VIEWS WHERE TABLE_SCHEMA=?", [$db]) as $r) {
            $l[] = "vista {$r->n}";
        }
        $l = array_values(array_unique($l));
        sort($l);
        return $l;
    }
}
