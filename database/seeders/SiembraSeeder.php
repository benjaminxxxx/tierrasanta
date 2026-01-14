<?php

namespace Database\Seeders;

use App\Models\Siembra;
use App\Support\ExcelHelper;
use App\Support\ValidacionHelper;
use DB;
use Exception;
use Illuminate\Database\Seeder;
use Str;

class SiembraSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Preparamos los datos primero (si esto falla, la tabla ni siquiera se toca)
        $data = $this->getDataFromExcel();

        // 2. Reseteamos la tabla (Esto pone el ID en 1 y borra todo)
        // Se hace fuera de la transacción para evitar el error de PDO
        Siembra::truncate();

        // 3. Insertamos los nuevos datos dentro de una transacción para asegurar integridad
        DB::transaction(function () use ($data) {
            $data->chunk(500)->each(function ($chunk) {
                Siembra::insert($chunk->toArray());
            });
        });

        $this->command->info('Tabla reseteada e importación completada desde ID 1.');
    }

    /**
     * Lógica de extracción y transformación
     */
    private function getDataFromExcel()
    {
        $filename = config('system.files.informacion_general');
        $sheet = ExcelHelper::cargarHoja('public', $filename, 'SIEMBRAS');
        $table = $sheet->getTableByName('Tabla_Siembras');

        if (!$table) {
            throw new Exception("No se encontró la tabla 'Tabla_Siembras'.");
        }

        $data = $sheet->rangeToArray($table->getRange(), null, true, false, true);
        $headers = array_map(fn($h) => Str::slug($h, '_'), array_shift($data));

        // Mapeo preventivo de campos para validar antes de procesar
        $camposValidos = ValidacionHelper::obtenerYValidarCampos(
            collect($data)->pluck(array_search('campo', $headers))->unique()->toArray()
        );

        return collect($data)
            ->map(fn($row) => array_combine($headers, $row))
            ->filter(fn($row) => !empty($row['campo']) && !empty($row['fecha_siembra']))
            ->values()
            ->map(function ($row, $index) use ($camposValidos) {
                $filaExcel = $index + 2;
                $campoNombre = $camposValidos[strtolower(trim($row['campo']))] ?? null;

                if (!$campoNombre) {
                    throw new Exception("Campo '{$row['campo']}' no válido (Fila #{$filaExcel})");
                }

                return [
                    'campo_nombre' => $campoNombre,
                    'fecha_siembra' => ExcelHelper::parseFechaExcel($row['fecha_siembra'], $filaExcel),
                    'fecha_renovacion' => ExcelHelper::parseFechaExcel($row['fecha_cierre'] ?? null, $filaExcel),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });
    }
}
