<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\Siembra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;

class SiembraSeeder extends Seeder
{
    public function run(): void
    {
        $path = Storage::disk('public')->path('informacion_general.xlsx');

        if (!file_exists($path)) {
            $this->command->error("No se encontró el archivo: informacion_general.xlsx");
            return;
        }

        // Obtener todas las hojas
        $hojas = Excel::toArray([], $path);
       
        $hojaSiembras = $hojas[1];

        if (!$hojaSiembras || count($hojaSiembras) < 2) {
            throw new \Exception("La hoja 'SIEMBRAS' no contiene datos suficientes o no existe.");
        }

        $rows = $hojaSiembras;

        // Índices de columnas: A = 0, B = 1, C = 2
        $campoIndex = 0;
        $fechaSiembraIndex = 1;
        $fechaCierreIndex = 2;

        // Traer nombres válidos de campos
        $camposValidos = Campo::pluck('nombre')->map(fn($c) => strtolower(trim($c)))->toArray();

        $upserts = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $fila = $i + 2;

            $campo = trim($row[$campoIndex] ?? '');
            $fechaSiembraRaw = $row[$fechaSiembraIndex] ?? null;
            $fechaCierreRaw = $row[$fechaCierreIndex] ?? null;

            if (empty($campo) || empty($fechaSiembraRaw)) {
                throw new \Exception("Fila #{$fila} está incompleta (campo o fecha de siembra vacía).");
            }

            if (!in_array(strtolower($campo), $camposValidos)) {
                throw new \Exception("Campo '{$campo}' (fila #{$fila}) no está registrado en la base de datos.");
            }

            $fechaSiembra = $this->parseFecha($fechaSiembraRaw, $fila);
            $fechaCierre = $this->parseFecha($fechaCierreRaw, $fila);

            $campoId = Campo::where('nombre', $campo)->first();

            if (!$campoId) {
                throw new \Exception("No se encontró el ID del campo '{$campo}' (fila #{$fila}).");
            }

            $upserts[] = [
                'campo_nombre' => $campoId->nombre,
                'fecha_siembra' => $fechaSiembra,
                'fecha_renovacion' => $fechaCierre,
            ];
        }

        foreach ($upserts as $data) {
            Siembra::updateOrInsert(
                ['campo_nombre' => $data['campo_nombre'], 'fecha_siembra' => $data['fecha_siembra']],
                ['fecha_renovacion' => $data['fecha_renovacion']]
            );
        }

        $this->command->info('Datos de siembras importados/actualizados correctamente desde la hoja SIEMBRAS.');
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
