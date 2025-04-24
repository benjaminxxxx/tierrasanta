<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CampoCampaniaSeeder extends Seeder
{
    public function run(): void
    {


        try {
            $sheet = ExcelHelper::cargarHoja('public', 'informacion_general.xlsx', 'CAMPAÑAS');
            $table = $sheet->getTableByName('table_campania');

            if (!$table) {
                throw new Exception("No se encontró la tabla table_campania.");
            }

            // Obtener el rango de la tabla (ejemplo: "A1:O20")
            $tableRange = $table->getRange();

            $data = $sheet->rangeToArray($tableRange, null, true, false, true);

            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

            // Reestructurar los datos con claves semánticas y resetear índices
            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

            $camposValidos = Campo::pluck('nombre')->toArray();
            $upserts = [];

            // Verificamos si hay algún campo inválido en el Excel
            $camposExcel = collect($data)->pluck('campo')->unique()->toArray();
            $camposInvalidos = array_diff($camposExcel, $camposValidos);

            if (!empty($camposInvalidos)) {
                throw new Exception("Los siguientes campos no existen en la base de datos: " . implode(', ', $camposInvalidos));
            }
            $i = 0;
            foreach ($data as $fila) {
                /*
                239 => array:5 [
                    "campo" => "B10"
                    "fecha_inicio" => 42400
                    "fecha_final" => 43112
                    "tipo_cambio" => 1
                    "nombre_campania" => "T.2016"
                  ]*/
                $i++;
                $fecha_inicio = $this->parseFecha($fila['fecha_inicio'], $i + 2);
                $fecha_final = $this->parseFecha($fila['fecha_final'] ?? null, $i + 2);


                $upserts[] = [
                    'campo' => $fila['campo'],
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_final,
                    'tipo_cambio' => $fila['tipo_cambio'] ?? null,
                    'nombre_campania' => $fila['nombre_campania'] ?? null,
                ];
            }

            foreach ($upserts as $data) {
                CampoCampania::updateOrInsert(
                    ['campo' => $data['campo'], 'fecha_inicio' => $data['fecha_inicio']],
                    [
                        'fecha_fin' => $data['fecha_fin'],
                        'tipo_cambio' => $data['tipo_cambio'],
                        'nombre_campania' => $data['nombre_campania']
                    ]
                );
            }

            $this->command->info('Datos de campañas importados/actualizados correctamente desde campanias.xlsx');

        } catch (\Throwable $th) {
            $this->command->error($th->getMessage());
            return;
        }
        
    }

    private function parseFecha($valor, $fila)
    {
        if (empty($valor))
            return null;

        try {
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($valor))->format('Y-m-d');
            }

            return Carbon::parse(str_replace(['.', '/', '\\'], '-', $valor))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Error al interpretar la fecha '{$valor}' en la fila #{$fila}: " . $e->getMessage());
        }
    }
}
