<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Archivo .sql.gz para REEMPLAZAR por completo la base de un hosting compartido (HostGator / phpMyAdmin), donde no se
 * puede borrar ni crear la base ni hay acceso a otras bases:
 *
 *   DB_DATABASE=santarita_nuevo php artisan sistema:exportar-reemplazo storage/app/reemplazo.sql.gz
 *
 * El archivo, al importarse en la base del servidor:
 *   1. Desactiva las llaves foráneas y borra TODAS las tablas y vistas que haya (los triggers se borran con su tabla).
 *   2. Crea las tablas, los datos y los triggers de la base local.
 *   3. Vuelve a activar las llaves foráneas.
 * Se quitan los DEFINER (el usuario del hosting no puede crear triggers a nombre de root) y la collation de MySQL 8
 * (utf8mb4_0900_ai_ci) que MySQL 5.7 / MariaDB no conocen.
 */
class SistemaExportarReemplazoCommand extends Command
{
    protected $signature = 'sistema:exportar-reemplazo {archivo=storage/app/reemplazo.sql.gz : archivo a crear (.sql o .sql.gz)}
        {--mysqldump= : ruta de mysqldump si no está en el PATH}';

    protected $description = 'Genera un .sql(.gz) que vacía la base del servidor y la reemplaza con la base local';

    public function handle(): int
    {
        $c = config('database.connections.' . config('database.default'));
        $base = $c['database'];
        $dump = $this->option('mysqldump') ?: $this->buscarMysqldump();
        if (!$dump) {
            $this->error('No encuentro mysqldump. Indica la ruta con --mysqldump=');
            return self::FAILURE;
        }
        $archivo = $this->argument('archivo');
        $crudo = $archivo . '.tmp';

        $tablas = DB::selectOne("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$base])->c;
        $triggers = DB::selectOne('SELECT COUNT(*) c FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ?', [$base])->c;
        $this->info("Exportando {$base}: {$tablas} tablas, {$triggers} triggers…");

        $cmd = sprintf('"%s" --host=%s --port=%s --user=%s --single-transaction --routines --triggers --no-tablespaces --hex-blob '
            . '--set-gtid-purged=OFF --default-character-set=utf8mb4 --skip-add-locks --result-file="%s" %s',
            $dump, $c['host'], $c['port'], $c['username'], $crudo, $base);
        putenv('MYSQL_PWD=' . ($c['password'] ?? ''));
        exec($cmd . ' 2>&1', $salida, $codigo);
        putenv('MYSQL_PWD');
        if ($codigo !== 0 || !is_file($crudo)) {
            $this->error("mysqldump falló:\n" . implode("\n", $salida));
            return self::FAILURE;
        }

        $cabecera = <<<'SQL'
-- Reemplazo completo de la base (generado por php artisan sistema:exportar-reemplazo).
-- Importar en la base del servidor: borra TODAS sus tablas y carga las de este archivo.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET SESSION group_concat_max_len = 10000000;

-- 1. Vistas existentes
SET @vistas = (SELECT GROUP_CONCAT(CONCAT('`', TABLE_NAME, '`')) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE());
SET @sql = IF(@vistas IS NULL, 'DO 0', CONCAT('DROP VIEW IF EXISTS ', @vistas));
PREPARE q FROM @sql; EXECUTE q; DEALLOCATE PREPARE q;

-- 2. Tablas existentes (sus triggers se borran con ellas)
SET @tablas = (SELECT GROUP_CONCAT(CONCAT('`', TABLE_NAME, '`')) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE');
SET @sql = IF(@tablas IS NULL, 'DO 0', CONCAT('DROP TABLE IF EXISTS ', @tablas));
PREPARE q FROM @sql; EXECUTE q; DEALLOCATE PREPARE q;

-- 3. Estructura, datos y triggers

SQL;
        $pie = "\nSET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n"
            . "-- Comprobación: deberían salir {$tablas} tablas y {$triggers} triggers\n"
            . "SELECT (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE') AS tablas,\n"
            . "       (SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()) AS triggers;\n";

        // Línea por línea: quitar DEFINER y la collation de MySQL 8; nada de USE/CREATE DATABASE
        $comprimir = str_ends_with($archivo, '.gz');
        $out = $comprimir ? gzopen($archivo, 'wb6') : fopen($archivo, 'wb');
        $escribir = fn($s) => $comprimir ? gzwrite($out, $s) : fwrite($out, $s);
        $escribir($cabecera);
        $in = fopen($crudo, 'rb');
        $definers = 0;
        while (($linea = fgets($in)) !== false) {
            if (preg_match('/^(USE |CREATE DATABASE)/', $linea)) {
                continue;
            }
            $linea = preg_replace('/\/\*!50017 DEFINER=`[^`]*`@`[^`]*`\s*\*\/\s*/', '', $linea, -1, $n1);
            $linea = preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/', '', $linea, -1, $n2);
            $definers += $n1 + $n2;
            $escribir(str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', $linea));
        }
        fclose($in);
        $escribir($pie);
        $comprimir ? gzclose($out) : fclose($out);
        unlink($crudo);

        $this->info("Listo: {$archivo} (" . round(filesize($archivo) / 1048576, 1) . " MB). DEFINER quitados: {$definers}.");
        $this->line('En phpMyAdmin: selecciona la base del servidor → Importar → este archivo. Al final muestra la cantidad de tablas y triggers.');
        return self::SUCCESS;
    }

    private function buscarMysqldump(): ?string
    {
        exec(PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>NUL' : 'command -v mysqldump 2>/dev/null', $r);
        if (!empty($r[0])) {
            return trim($r[0]);
        }
        $laragon = glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [];
        return $laragon ? end($laragon) : null;
    }
}
