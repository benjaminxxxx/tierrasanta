<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaFiltrado;
use App\Models\CochinillaInfestacion;
use App\Models\CochinillaVenteado;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CochinillaInfestacionesSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $sheet = ExcelHelper::cargarHoja('public', 'informacion_general.xlsx', 'INFESTACIONES');
            $table = $sheet->getTableByName('Tabla_infestaciones');

            if (!$table) {
                throw new Exception("No se encontró la tabla Tabla_infestaciones.");
            }

            $tableRange = $table->getRange();
            $data = $sheet->rangeToArray($tableRange, null, true, false, true);
            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

            // Reestructurar los datos con claves semánticas y resetear índices
            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

            $upserts = [];

            // Obtener todos los nombres de campo desde el Excel (ignorando encabezados)
            $camposDesdeExcel = collect($data)
                ->pluck("campo_nombre") // columna de nombres de campos
                ->filter() // eliminar nulos o vacíos
                ->unique()
                ->values();
            $camposDesdeExcelOrigen = collect($data)
                ->pluck("campo_origen_nombre") // columna de nombres de campos
                ->filter() // eliminar nulos o vacíos
                ->unique()
                ->values();
            // Obtener campos válidos de la base de datos
            $camposEnBD = Campo::whereIn('nombre', $camposDesdeExcel)->pluck('nombre');
            $camposOrigenEnBD = Campo::whereIn('nombre', $camposDesdeExcelOrigen)->pluck('nombre');
            $camposFaltantes = $camposDesdeExcel->diff($camposEnBD);
            $camposFaltantesOrigen = $camposDesdeExcelOrigen->diff($camposOrigenEnBD);
            if ($camposFaltantes->isNotEmpty()) {
                throw new Exception("Los siguientes campos no existen en la base de datos: " . $camposFaltantes->join(', '));
            }
            if ($camposFaltantesOrigen->isNotEmpty()) {
                throw new Exception("Los siguientes campos no existen en la base de datos: " . $camposFaltantesOrigen->join(', '));
            }
            
            foreach ($data as $index => $fila) {
                /*
                array:14 [
                    "tipo_infestacion" => "Infestacion"
                    "fecha" => 42174
                    "campo_nombre" => "B9"
                    "area" => 3.523
                    "campania" => "T.2015"
                    "kg_madres" => 200.2
                    "kg_madres_por_ha" => 56.826568265683
                    "campo_origen_nombre" => "12"
                    "metodo" => "carton"
                    "capacidad_envase" => 525
                    "numero_envases" => 220
                    "infestadores" => 115500
                    "madres_por_infestador" => 0.0017333333333333
                    "infestadores_por_ha" => 32784.558614817
                 ]
                 */
                $fila_numero = $index + 2; // fila real en Excel

                if ($fila_numero<1871) {
                    //los valores en adelante recien seran registrados
                    continue;
                }
                //dd($fila_numero,$fila);

                $fecha = ExcelHelper::parseFecha($fila['fecha'], $fila_numero);
                $tipo_infestacion = $fila['tipo_infestacion'] === 'Infestacion' ? 'infestacion' : 'reinfestacion';
                $campo_campania_id = null;
                $campania = CampoCampania::where("campo", $fila['campo_nombre'])
                    ->whereDate('fecha_inicio', '<=', $fecha)
                    ->orderBy('fecha_inicio', 'desc')
                    ->first();
                if ($campania) {
                    $campo_campania_id = $campania->id;
                }
                $upserts[] = [
                    'tipo_infestacion' => $tipo_infestacion,
                    'fecha' => $fecha,
                    'campo_nombre' => $fila['campo_nombre'],
                    'area' => $fila['area'] ?? null,
                    'campo_campania_id' => $campo_campania_id,
                    'kg_madres' => $fila['kg_madres'] ?? 0,
                    'kg_madres_por_ha' => $fila['kg_madres_por_ha'] ?? 0,
                    'campo_origen_nombre' => $fila['campo_origen_nombre'] ?? 0,
                    'metodo' => $fila['metodo'] ?? 'carton',
                    'numero_envases' => $fila['numero_envases'] ?? 0,
                    'capacidad_envase' => $fila['capacidad_envase'] ?? 0,
                    'infestadores' => $fila['infestadores'] ?? 0,
                    'madres_por_infestador' => $fila['madres_por_infestador'] ?? 0,
                    'infestadores_por_ha' => $fila['infestadores_por_ha'] ?? 0,
                ];
            }

            CochinillaInfestacion::insert($upserts);

            $this->command->info('Datos actualizados correctamentexlsx');

        } catch (\Throwable $th) {
            $this->command->error($th->getMessage());
            return;
        }

    }


}
