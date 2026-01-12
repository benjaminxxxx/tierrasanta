<?php

namespace Database\Seeders;

use App\Services\Campo\Gestion\CampoServicio;
use App\Support\ExcelHelper;
use App\Support\ValidacionHelper;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
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

        $camposFiltrados = ValidacionHelper::obtenerYValidarCampos($camposDesdeExcel->toArray());
        
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
               
        
                $fecha = ExcelHelper::parseFecha($fila["fecha"], $fila_numero);
        
                if (!$fila["campo"]) {
                    continue;
                }

                $loteCodigo = $fila["lote"]??null;
                $campo = mb_strtolower(trim($fila["campo"]));
                $area = (float)(trim($fila["area"]));
        
                $codigoObservacion = $this->generarCodigoSlug($fila["obs"]);
                $loteCodigoPrincipal = $loteCodigo ?? explode('.', $fila["sub_lote"])[0];
        
                $ingreso = CochinillaIngreso::firstOrCreate(
                    ['lote' => $loteCodigoPrincipal],
                    [
                        'campo' => $camposFiltrados[$campo],
                        'fecha' => $fecha,
                        'area' => $area,
                        'observacion' => $codigoObservacion,
                    ]
                );
        
                if ($fila["lote"] || trim($fila["lote"]) != '') {
                    $ingreso->update([
                        'fecha' => $fecha,
                        'campo' => $camposFiltrados[$campo],
                        'area' => $area,
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
        
                $key = $camposFiltrados[$campo] . '|' . $fila["campana"];
                $variedadCultivos[$key] = $fila["cultivo"];
            }
        
            foreach ($variedadCultivos as $key => $variedad) {
                [$campoNombre, $campania] = explode('|', $key);
                $relacion = CampoCampania::where('campo', $campoNombre)
                    ->where('nombre_campania', $campania)
                    ->first();
        
                if ($relacion) {
                    $relacion->update(['variedad_tuna' => $variedad]);
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
