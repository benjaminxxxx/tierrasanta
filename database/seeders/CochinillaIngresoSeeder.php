<?php

namespace Database\Seeders;

use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CochinillaIngresoSeeder extends Seeder
{
    public function run(): void
    {
        $sheet = ExcelHelper::cargarHoja('public', 'informacion_general.xlsx', 'INGRESOS');
        $table = $sheet->getTableByName('Tabla_ingresos');

        if (!$table) {
            throw new Exception("No se encontró la tabla Tabla_ingresos.");
        }

        $tableRange = $table->getRange();
        $data = $sheet->rangeToArray($tableRange, null, true, false, true);
        $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

        // Reestructurar los datos con claves semánticas y resetear índices
        $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

        $upserts = [];

        $variedadCultivos = [];
        /*
        $loteIndex = 0;
        $subloteIndex = 1;
        $fechaIndex = 2;
        $campoIndex = 3;
        $campaniaIndex = 4;
        $cultivoIndex = 5;
        $fechaSiembra = 6;
        $kilo_totalIndex = 7;
        $obsIndex = 8;*/

        // Obtener todos los nombres de campo desde el Excel (ignorando encabezados)
        $camposDesdeExcel = collect($data)
            ->pluck("campo") // columna de nombres de campos
            ->filter() // eliminar nulos o vacíos
            ->unique()
            ->values();

        // Obtener campos válidos de la base de datos
        $camposEnBD = Campo::whereIn('nombre', $camposDesdeExcel)->pluck('nombre');

        // Verificar si hay campos del Excel que no existen en la BD
        $camposFaltantes = $camposDesdeExcel->diff($camposEnBD);

        if ($camposFaltantes->isNotEmpty()) {
            throw new Exception("Los siguientes campos no existen en la base de datos: " . $camposFaltantes->join(', '));
        }


        // Obtener los textos de observación desde Excel
        $observacionesDesdeExcel = collect($data)
            ->pluck("obs") // columna de nombres de campos
            ->filter() // eliminar nulos o vacíos
            ->map(fn($texto) => $this->generarCodigoSlug($texto))
            ->unique()
            ->values();


        // Obtener códigos válidos desde la BD
        $observacionesEnBD = CochinillaObservacion::whereIn('codigo', $observacionesDesdeExcel)->pluck('codigo');

        // Verificar si hay códigos que no existen
        $observacionesFaltantes = $observacionesDesdeExcel->diff($observacionesEnBD);

        if ($observacionesFaltantes->isNotEmpty()) {
            throw new Exception("Las siguientes observaciones no existen en la base de datos: " . $observacionesFaltantes->join(', '));
        }

        try {
            DB::beginTransaction();
        
            foreach ($data as $index => $fila) {
                $fila_numero = $index + 2;
                if ($fila_numero <= 3678) {
                    continue;
                }
        
                $fecha = ExcelHelper::parseFecha($fila["fecha"], $fila_numero);
        
                if (!$fila["campo"]) {
                    continue;
                }

                $loteCodigo = $fila["lote"]??null;
        
                $codigoObservacion = $this->generarCodigoSlug($fila["obs"]);
                $loteCodigoPrincipal = $loteCodigo ?? explode('.', $fila["sub_lote"])[0];
        
                $ingreso = CochinillaIngreso::firstOrCreate(
                    ['lote' => $loteCodigoPrincipal],
                    [
                        'campo' => $fila["campo"],
                        'fecha' => $fecha,
                        'observacion' => $codigoObservacion,
                    ]
                );
        
                if ($fila["lote"] || trim($fila["lote"]) != '') {
                    $ingreso->update([
                        'fecha' => $fecha,
                        'campo' => $fila["campo"],
                        'observacion' => $codigoObservacion,
                        'total_kilos' => $fila["total_kilos"],
                    ]);
                }
        
                if ($fila["sub_lote"] || trim($fila["sub_lote"]) != '') {
                    CochinillaIngresoDetalle::updateOrCreate(
                        [
                            'cochinilla_ingreso_id' => $ingreso->id,
                            'sublote_codigo' => $fila["sub_lote"],
                        ],
                        [
                            'fecha' => $fecha,
                            'total_kilos' => $fila["total_kilos"],
                            'observacion' => $codigoObservacion,
                        ]
                    );
                }
        
                $key = $fila["campo"] . '|' . $fila["campana"];
                $variedadCultivos[$key] = $fila["cultivo"];
            }
        
            foreach ($variedadCultivos as $key => $variedad) {
                [$campoNombre, $campania] = explode('|', $key);
                $relacion = CampoCampania::where('campo', $campoNombre)
                    ->where('nombre_campania', $campania)
                    ->first();
        
                if ($relacion) {
                    $relacion->update(['variedad_tuna' => $variedad]);
                } else {
                    //throw new Exception("No se encontró CampoCampania para campo '{$campoNombre}' y campaña '{$campania}'");
                }
            }
        
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // Aquí puedes registrar el error o lanzarlo de nuevo
            $this->command->error($e->getMessage());
        }
    }

    private function generarCodigoSlug($texto = null)
    {
        if (!$texto) {
            return '';
        }
        $texto = strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n'
        ]);
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto); // quita signos raros
        $texto = preg_replace('/\s+/', '_', $texto); // reemplaza espacios por guion bajo
        return $texto;
    }
}
