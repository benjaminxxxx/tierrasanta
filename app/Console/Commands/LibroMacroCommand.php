<?php

namespace App\Console\Commands;

use App\Services\Sistema\LibroExcel\SistemaLibroExcel;
use Illuminate\Console\Command;

/**
 * Saca el código VBA de los libros Excel (botones Actualizar / Subir) de una plantilla .xlsm armada en Excel:
 *   1. Excel → libro en blanco → Alt+F11 → Archivo → Importar archivo → resources/excel/TierraSanta.bas
 *   2. Guardar como "Libro de Excel habilitado para macros" (.xlsm)
 *   3. php artisan libro:macro ruta/plantilla.xlsm
 * Copia su xl/vbaProject.bin a resources/excel/libros-vba.bin; desde ahí cada descarga sale como .xlsm con la macro.
 * Repetir cuando cambie TierraSanta.bas.
 */
class LibroMacroCommand extends Command
{
    protected $signature = 'libro:macro {plantilla : archivo .xlsm guardado en Excel con el módulo TierraSanta}';

    protected $description = 'Actualiza la macro de los libros Excel desde una plantilla .xlsm';

    public function handle(): int
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->argument('plantilla')) !== true) {
            $this->error('No se pudo abrir la plantilla (¿es un .xlsm?).');
            return self::FAILURE;
        }
        $bin = $zip->getFromName('xl/vbaProject.bin');
        $zip->close();
        if ($bin === false) {
            $this->error('La plantilla no tiene macros: importa TierraSanta.bas y guárdala como "Libro de Excel habilitado para macros".');
            return self::FAILURE;
        }
        if (!str_contains($bin, 'TierraSanta') || !str_contains($bin, 'Auto_Open')) {
            $this->warn('Ojo: no se encontró el módulo TierraSanta / Auto_Open dentro de la macro. Revisa que se importó el archivo correcto.');
        }
        file_put_contents(base_path(SistemaLibroExcel::MACRO_BIN), $bin);
        $this->info('Macro actualizada (' . number_format(strlen($bin)) . ' bytes): ' . SistemaLibroExcel::MACRO_BIN);
        return self::SUCCESS;
    }
}
