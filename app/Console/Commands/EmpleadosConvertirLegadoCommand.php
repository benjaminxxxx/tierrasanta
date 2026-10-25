<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Planilla\Empleados\PlanillaEmpleadosLegadoDefinicion;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDescarga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Arma el Excel de empleados (estructura actual) desde una base con la estructura ANTIGUA, listo para subir con la macro
 * como carga inicial (botón "Subir cambios" sin actualizar antes):
 *   php artisan empleados:convertir-legado ts_produccion --correo=admin@empresa.com --salida="EMPLEADOS (carga inicial).xlsm"
 * La clave de la macro se emite para ese usuario en ESTE sistema (.env): súbelo a este mismo sistema.
 * Solo lee la base antigua.
 */
class EmpleadosConvertirLegadoCommand extends Command
{
    protected $signature = 'empleados:convertir-legado {base : base de datos con la estructura antigua (misma conexión MySQL)}
        {--correo= : usuario de este sistema a cuyo nombre se subirá (macro)} {--salida= : archivo a crear (.xlsm o .xlsx)}';

    protected $description = 'Convierte empleados de la estructura antigua al Excel de empleados (carga inicial con macro)';

    public function handle(SistemaLibroExcelDescarga $descarga): int
    {
        $usuario = User::where('email', $this->option('correo'))->first();
        if (!$usuario) {
            $this->error('Indica --correo de un usuario de este sistema (a su nombre se emite la clave de la macro).');
            return self::FAILURE;
        }
        Config::set('database.connections.legado', array_merge(config('database.connections.' . config('database.default')), ['database' => $this->argument('base')]));
        DB::purge('legado');
        $conexion = DB::connection('legado');
        if (!$conexion->getSchemaBuilder()->hasColumn('plan_contratos', 'remuneracion_basica')) {
            $this->error("La base {$this->argument('base')} no tiene la estructura antigua (plan_contratos.remuneracion_basica).");
            return self::FAILURE;
        }

        $def = app(PlanillaEmpleadosLegadoDefinicion::class)->desde($conexion);
        [$libro, $nombre] = $descarga->preparar($usuario, 'empleados', null, $def, 'carga');
        $salida = $this->option('salida') ?: str_replace('.xls', ' (carga inicial).xls', $nombre);
        (new Xlsx($libro))->save($salida);

        foreach ($def->avisosConversion() as $a) {
            $this->warn($a);
        }
        $filas = $def->filasSistema();
        $this->table(['Hoja', 'Filas'], collect($filas)->map(fn($f, $h) => [$h, count($f)])->values()->all());
        $this->info("Listo: {$salida}");
        $this->line('Ábrelo, habilita el contenido y usa "Subir cambios" (no "Actualizar": es una carga inicial). La clave vence en 7 días.');
        return self::SUCCESS;
    }
}
