<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Support\ExcelHelper;
use App\Support\ValidacionHelper;
use DB;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CochinillaInfestacionesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Preparar datos (si falla, no toca la BD)
        $data = $this->getDataFromExcel();

        // 2. Borrado lógico completo (NO truncate)
        CochinillaInfestacion::query()->delete();

        // 3. Inserción segura
        DB::transaction(function () use ($data) {
            $data->chunk(500)->each(function ($chunk) {
                CochinillaInfestacion::insert($chunk->toArray());
            });
        });

        $this->command->info('Tabla cochinilla_infestaciones limpiada e importada correctamente.');
     
    }
    /**
     * Extracción y transformación desde Excel
     */
    private function getDataFromExcel()
    {
        $filename = config('system.files.informacion_general');
        $sheet = ExcelHelper::cargarHoja('public', $filename, 'INFESTACIONES');
        $table = $sheet->getTableByName('Tabla_infestaciones');

        if (!$table) {
            throw new Exception("No se encontró la tabla 'Tabla_infestaciones'.");
        }

        $data = $sheet->rangeToArray($table->getRange(), null, true, false, true);
        $headers = array_map(fn($h) => Str::slug($h, '_'), array_shift($data));

        $rows = collect($data)
            ->map(fn($row) => array_combine($headers, $row))
            ->values();

        /*
         |-----------------------------------------------------------
         | Validación preventiva de campos (destino y origen)
         |-----------------------------------------------------------
         */
        $camposDestino = ValidacionHelper::obtenerYValidarCampos(
            $rows->pluck('campo_nombre')->filter()->unique()->toArray()
        );

        $camposOrigen = ValidacionHelper::obtenerYValidarCampos(
            $rows->pluck('campo_origen_nombre')->filter()->unique()->toArray()
        );

        return $rows
            ->filter(fn($row) => !empty($row['campo_nombre']) && !empty($row['fecha']))
            ->map(function ($row, $index) use ($camposDestino, $camposOrigen) {

                $filaExcel = $index + 2;

                $campoNombre = $camposDestino[strtolower(trim($row['campo_nombre']))] ?? null;
                if (!$campoNombre) {
                    throw new Exception("Campo destino '{$row['campo_nombre']}' no válido (Fila #{$filaExcel})");
                }

                $campoOrigen = null;
                if (!empty($row['campo_origen_nombre'])) {
                    $campoOrigen = $camposOrigen[strtolower(trim($row['campo_origen_nombre']))] ?? null;
                    if (!$campoOrigen) {
                        throw new Exception("Campo origen '{$row['campo_origen_nombre']}' no válido (Fila #{$filaExcel})");
                    }
                }

                $fecha = ExcelHelper::parseFechaExcel($row['fecha'], $filaExcel);

                return [
                    'tipo_infestacion' => $row['tipo_infestacion'] === 'Infestacion'
                        ? 'infestacion'
                        : 'reinfestacion',

                    'fecha' => $fecha,
                    'campo_nombre' => $campoNombre,

                    'area' => $row['area'] ?? null,
                    'kg_madres' => $row['kg_madres'] ?? 0,
                    'kg_madres_por_ha' => $row['kg_madres_por_ha'] ?? 0,

                    'campo_origen_nombre' => $campoOrigen,
                    'metodo' => $row['metodo'] ?? 'carton',
                    'numero_envases' => $row['numero_envases'] ?? 0,
                    'capacidad_envase' => $row['capacidad_envase'] ?? 0,

                    'infestadores' => $row['infestadores'] ?? 0,
                    'madres_por_infestador' => $row['madres_por_infestador'] ?? 0,
                    'infestadores_por_ha' => $row['infestadores_por_ha'] ?? 0,

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });
    }

}
