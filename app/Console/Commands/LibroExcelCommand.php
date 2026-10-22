<?php

namespace App\Console\Commands;

use App\Services\Sistema\LibroExcel\SistemaLibroExcel;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelAnalisis;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelProceso;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelRegistro;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Libros Excel desde la consola (lo mismo que el escritorio de archivos del inicio):
 *   php artisan libro:excel recursos exportar RECURSOS.xlsx
 *   php artisan libro:excel insumos importar INSUMOS.xlsx            (solo muestra el análisis)
 *   php artisan libro:excel empleados importar EMPLEADOS.xlsx --guardar
 * Libros: recursos, insumos, empleados. Un archivo de otro sistema (otro dominio) se puede guardar; uno de este sistema
 * solo si se descargó con los datos actuales (marca de versión).
 */
class LibroExcelCommand extends Command
{
    protected $signature = 'libro:excel {libro : recursos, insumos o empleados} {accion : exportar o importar} {ruta : archivo .xlsx} {--guardar : Al importar, guarda los cambios}';

    protected $description = 'Exporta o importa un libro Excel del sistema (recursos, insumos, empleados)';

    public function handle(SistemaLibroExcel $excel): int
    {
        $def = SistemaLibroExcelRegistro::definicion($this->argument('libro'));
        $ruta = $this->argument('ruta');
        if ($this->argument('accion') === 'exportar') {
            (new Xlsx($excel->exportar($def)))->save($ruta);
            $this->info($def->reglas()::TITULO . " guardado en {$ruta}");
            return self::SUCCESS;
        }
        if ($this->argument('accion') !== 'importar') {
            $this->error('La acción debe ser exportar o importar.');
            return self::FAILURE;
        }

        $analisis = app(SistemaLibroExcelAnalisis::class)->analizar($def, $excel->leer($def, $ruta));
        $this->line('Versión del archivo: ' . $analisis['version']['estado'] . ($analisis['version']['descargado'] ? " (descargado {$analisis['version']['descargado']})" : ''));
        foreach ($analisis['avisos'] as $a) {
            $this->warn($a);
        }
        foreach ($analisis['faltantes'] as $hoja => $valores) {
            $this->warn("Falta cargar antes {$hoja}: " . implode(', ', array_slice($valores, 0, 15)));
        }
        $filas = [];
        foreach ($analisis['hojas'] as $h) {
            $filas[] = [$h['titulo'], $h['resumen']['nuevo'] ?? 0, $h['resumen']['cambia'] ?? 0, $h['resumen']['igual'] ?? 0, $h['resumen']['error'] ?? 0, count($h['solo_sistema'])];
            foreach ($h['filas'] as $f) {
                foreach ($f['errores'] as $e) {
                    $this->line("  <fg=red>{$h['titulo']} fila {$f['fila']} ({$f['clave']}):</> {$e}");
                }
            }
        }
        if ($m = $analisis['matriz']) {
            $filas[] = [$m['titulo'], $m['resumen']['nuevo'] ?? 0, $m['resumen']['cambia'] ?? 0, $m['resumen']['igual'] ?? 0, 0, count($m['solo_sistema'])];
            foreach ($m['avisos'] as $a) {
                $this->warn($a);
            }
        }
        $this->table(['Hoja', 'Nuevos', 'Cambian', 'Iguales', 'Errores', 'Solo en sistema'], $filas);

        if (!$this->option('guardar')) {
            $this->line('Solo análisis. Para guardar: --guardar');
            return self::SUCCESS;
        }
        $guardado = app(SistemaLibroExcelProceso::class)->importar($def, $ruta);
        foreach ($guardado as $hoja => $n) {
            $this->line("  {$hoja}: {$n}");
        }
        $this->info('Guardado.');
        return self::SUCCESS;
    }
}
